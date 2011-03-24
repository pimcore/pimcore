var FreemarkerParser = Editor.Parser = (function() {
        var autoSelfClosers = {"else": true, "elseif": true};

        // Simple stateful tokenizer for Freemarker documents. Returns a
        // MochiKit-style iterator, with a state property that contains a
        // function encapsulating the current state. See tokenize.js.
        var tokenizeFreemarker = (function() {
                function inText(source, setState) {
                    var ch = source.next();
                    if (ch == "<") {
                        if (source.equals("!")) {
                            source.next();
                            if (source.lookAhead("--", true)) {
                                setState(inBlock("freemarker-comment", "-->"));
                                return null;
                            } else {
                                return "freemarker-text";
                            }
                        } else {
                            source.nextWhileMatches(/[\#\@\/]/);
                            setState(inFreemarker(">"));
                            return "freemarker-boundary";
                        }
                    }
                    else if (ch == "[") {
                        if(source.matches(/[\#\@]/)) {
                            setState(pendingFreemarker(source.peek(), "]", false));
                            return "freemarker-boundary";
                        } else if(source.matches(/\//)) {
                            setState(pendingFreemarkerEnd("]"));
                            return "freemarker-boundary";
                        } else {
                            return "freemarker-text";
                        }
                    }
                    else if (ch == "$") {
                        if(source.matches(/[\{\w]/)) {
                            setState(pendingFreemarker("{", "}", true));
                            return "freemarker-boundary";
                        } else {
                            return "freemarker-text";
                        }
                    }
                    else {
                        source.nextWhileMatches(/[^\$<\n]/);
                        return "freemarker-text";
                    }
                }

                function pendingFreemarker(startChar, endChar, nextCanBeIdentifier) {
                    return function(source, setState) {
                        var ch = source.next();
                        if(ch == startChar) {
                            setState(inFreemarker(endChar));
                            return "freemarker-boundary";
                        } else if(nextCanBeIdentifier) {
                            source.nextWhileMatches(/\w/);
                            setState(inText);
                            return "freemarker-identifier";
                        } else {
                            setState(inText);
                            return null;
                        }
                    }
                }

                function pendingFreemarkerEnd(endChar) {
                    return function(source, setState) {
                        var ch = source.next();
                        if(ch == "/") {
                            setState(pendingFreemarker(source.peek(), endChar, false));
                            return "freemarker-boundary";
                        } else {
                            setState(inText);
                            return null;
                        }
                    }
                }

                function inFreemarker(terminator) {
                    return function(source, setState) {
                        var ch = source.next();
                        if (ch == terminator) {
                            setState(inText);
                            return "freemarker-boundary";
                        } else if (/[?\/]/.test(ch) && source.equals(terminator)) {
                            source.next();
                            setState(inText);
                            return "freemarker-boundary";
                        } else if(/[?!]/.test(ch)) {
                            if(ch == "?") {
                                if(source.peek() == "?") {
                                    source.next();
                                } else {
                                    setState(inBuiltIn(inFreemarker(terminator)));
                                }
                            }
                            return "freemarker-punctuation";
                        } else if(/[()+\/\-*%=]/.test(ch)) {
                            return "freemarker-punctuation";
                        } else if (/[0-9]/.test(ch)) {
                            source.nextWhileMatches(/[0-9]+\.?[0-9]* /);
                            return "freemarker-number";
                        } else if (/\w/.test(ch)) {
                            source.nextWhileMatches(/\w/);
                            return "freemarker-identifier";
                        } else if(/[\'\"]/.test(ch)) {
                            setState(inString(ch, inFreemarker(terminator)));
                            return "freemarker-string";
                        } else {
                            source.nextWhileMatches(/[^\s\u00a0<>\"\'\}?!\/]/);
                            return "freemarker-generic";
                        }
                    };
                }

                function inBuiltIn(nextState) {
                    return function(source, setState) {
                        var ch = source.peek();
                        if(/[a-zA-Z_]/.test(ch)) {
                            source.next();
                            source.nextWhileMatches(/[a-zA-Z_0-9]+/);
                            setState(nextState);
                            return "freemarker-builtin";
                        } else {
                            setState(nextState);
                        }
                    };
                }

                function inString(quote, nextState) {
                    return function(source, setState) {
                        while (!source.endOfLine()) {
                            if (source.next() == quote) {
                                setState(nextState);
                                break;
                            }
                        }
                        return "freemarker-string";
                    };
                }

                function inBlock(style, terminator) {
                    return function(source, setState) {
                        while (!source.endOfLine()) {
                            if (source.lookAhead(terminator, true)) {
                                setState(inText);
                                break;
                            }
                            source.next();
                        }
                        return style;
                    };
                }

                return function(source, startState) {
                    return tokenizer(source, startState || inText);
                };
            })();

        // The parser. The structure of this function largely follows that of
        // parseXML in parsexml.js 
        function parseFreemarker(source) {
            var tokens = tokenizeFreemarker(source), token;
            var cc = [base];
            var tokenNr = 0, indented = 0;
            var currentTag = null, context = null;
            var consume;
            
            function push(fs) {
                for (var i = fs.length - 1; i >= 0; i--)
                    cc.push(fs[i]);
            }
            function cont() {
                push(arguments);
                consume = true;
            }
            function pass() {
                push(arguments);
                consume = false;
            }
            
            function markErr() {
                token.style += " freemarker-error";
            }

            function expect(text) {
                return function(style, content) {
                    if (content == text) cont();
                    else {markErr(); cont(arguments.callee);}
                };
            }

            function pushContext(tagname, startOfLine) {
                context = {prev: context, name: tagname, indent: indented, startOfLine: startOfLine};
            }

            function popContext() {
                context = context.prev;
            }

            function computeIndentation(baseContext) {
                return function(nextChars, current, direction, firstToken) {
                    var context = baseContext;

                    nextChars = getThreeTokens(firstToken);

                    if ((context && /^<\/\#/.test(nextChars)) ||
                        (context && /^\[\/\#/.test(nextChars))) {
                        context = context.prev;
                    } 

                    while (context && !context.startOfLine) {
                        context = context.prev;
                    }

                    if (context) {
                        if(/^<\#else/.test(nextChars) ||
                           /^\[\#else/.test(nextChars)) {
                            return context.indent;
                        }
                        return context.indent + indentUnit;
                    } else {
                        return 0;
                    }
                };
            }

            function getThreeTokens(firstToken) {
                var secondToken = firstToken ? firstToken.nextSibling : null;
                var thirdToken = secondToken ? secondToken.nextSibling : null;

                var nextChars = (firstToken && firstToken.currentText) ? firstToken.currentText : "";
                if(secondToken && secondToken.currentText) {
                    nextChars = nextChars + secondToken.currentText;
                    if(thirdToken && thirdToken.currentText) {
                        nextChars = nextChars + thirdToken.currentText;
                    }
                }

                return nextChars;
            }

            function base() {
                return pass(element, base);
            }

            var harmlessTokens = { "freemarker-text": true, "freemarker-comment": true };

            function element(style, content) {
                if (content == "<#") {
                    cont(tagname, notEndTag, endtag("/>", ">", tokenNr == 1));
                } else if (content == "</#") { 
                    cont(closetagname, expect(">"));
                } else if(content == "[" && style == "freemarker-boundary") {
                    cont(hashOrCloseHash);
                } else {
                    cont();
                }
            }

            function hashOrCloseHash(style, content) {
                if(content == "#") {
                    cont(tagname, notHashEndTag, endtag("/]", "]", tokenNr == 2));
                } else if(content == "/") {
                    cont(closeHash);
                } else {
                    markErr();
                }
            }

            function closeHash(style, content) {
                if(content == "#") {
                    cont(closetagname, expect("]"));
                } else {
                    markErr();
                }
            }


            function tagname(style, content) {
                if (style == "freemarker-identifier") {
                    currentTag = content.toLowerCase();
                    token.style = "freemarker-directive";
                    cont();
                } else {
                    currentTag = null;
                    pass();
                }
            }
            
            function closetagname(style, content) {
                if (style == "freemarker-identifier") {
                    token.style = "freemarker-directive";
                    if (context && content.toLowerCase() == context.name) {
                        popContext();
                    } else {
                        markErr();
                    }
                }
                cont();
            }

            function notEndTag(style, content) {
                if (content == "/>" || content == ">") {
                    pass(); 
                } else {
                    cont(notEndTag);
                }
            }

            function notHashEndTag(style, content) {
                if (content == "/]" || content == "]") {
                    pass(); 
                } else {
                    cont(notHashEndTag);
                }
            }

            function endtag(closeTagPattern, endTagPattern, startOfLine) {
                return function(style, content) {
                    if (content == closeTagPattern || (content == endTagPattern && autoSelfClosers.hasOwnProperty(currentTag))) {
                        cont();
                    } else if (content == endTagPattern) {
                        pushContext(currentTag, startOfLine); 
                        cont();
                    } else {
                        markErr(); 
                        cont(arguments.callee);
                    }
                };
            }
            
            
            return {
                indentation: function() { return indented; },
                    
                next: function() {
                    token = tokens.next();
                    if (token.style == "whitespace" && tokenNr == 0)
                        indented = token.value.length;
                    else
                        tokenNr++;
                    if (token.content == "\n") {
                        indented = tokenNr = 0;
                        token.indentation = computeIndentation(context);
                    }
                    
                    if (token.style == "whitespace" || token.type == "freemarker-comment")
                        return token;
                    
                    while(true) {
                        consume = false;
                        cc.pop()(token.style, token.content);
                        if (consume) {
                            return token;
                        }
                    }
                },
                    
                copy: function(){
                    var _cc = cc.concat([]), _tokenState = tokens.state, _context = context;
                    var parser = this;
                    
                    return function(input){
                        cc = _cc.concat([]);
                        tokenNr = indented = 0;
                        context = _context;
                        tokens = tokenizeFreemarker(input, _tokenState);
                        return parser;
                    };
                }
            };
        }

        return {
            make: parseFreemarker,
            electricChars: ">"
        };
    })();
