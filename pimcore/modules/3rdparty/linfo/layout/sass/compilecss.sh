#!/bin/bash

# Joe Gillotti - 7/19/14 - GPL
# Compile sass templates to css using http://sass-lang.com/
for file in theme_*.sass; do
  sass --unix-newlines --style compressed --sourcemap=none $file ../${file%.sass}.css
done

# Compile mobile sass to css
sass --unix-newlines --style compressed --sourcemap=none mobile.sass ../mobile.css
# Compile icon sass to css
sass --unix-newlines --style compressed --sourcemap=none icons.sass ../icons.css
