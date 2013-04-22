// this string is a comment and should be ignored
-- this is also a comment according to phing
# even this is still a comment

insert into "duh" (foo) values ('duh')
duh

update "duh?" -- I should not be ignored
set foo = '${foo.value}'
duh

insert into dump (message) values ('I am a statement not ending with a delimiter')