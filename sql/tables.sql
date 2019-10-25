
CREATE TABLE match (
	id       serial PRIMARY KEY,
	moves    text,
	totmoves integer,
	event    text,
	date     text,
	site     text,
	white    text,
	black    text,
	result   integer, -- 0=tie, 1=white, 2=black, 3=others
	whiteelo smallint,
	blackelo smallint
);

CREATE TABLE boardstate (
	idmatch  integer REFERENCES match(id),
	nummove  smallint, -- seq: 11,12,21,22,31,32,...
	origmove text,
	state    text
);
