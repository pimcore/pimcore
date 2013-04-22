-- this string is a comment and should be ignored
// this is also a comment according to phing
# even this is still a comment

insert into foo (bar, "strange;name""indeed") values ('bar''s value containing ;', 'a value for strange named column');

delete
from
foo where bar = '${bar.value}'; update dump -- I should not be ignored
set message = 'I am a string with \\ backslash \' escapes and semicolons;'; 

delimiter //

-- note: the double slash is no longer a comment
create procedure setfoo(newfoo int)
begin
    set @foo = newfoo;
end
//

insert into dump (message) values ('I am a statement not ending with a delimiter')