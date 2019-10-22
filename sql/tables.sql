
CREATE TABLE match {
	id       serial,
	pgn      text,
	event    text,
	site     text,
	white    text,
	black    text,
	result   integer, -- 0=tie, 1=white, 2=black, 3=others
	whiteelo smallint,
	blackelo smallint
}

CREATE TABLE board {
	idmatch integer REFERENCES match(id),
	move    smallint, -- seq: 1,2,11,12,21,22,31,32,...
	state   bytea
}
