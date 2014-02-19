PRAGMA foreign_keys=OFF;
BEGIN TRANSACTION;
CREATE TABLE Document (
  idDocument integer PRIMARY KEY,
  pathDocument varchar(255) NOT NULL default '',
  nbNotes integer NOT NULL default '0',
  realname varchar(255) NOT NULL default '',
  tmpname varchar(255) NOT NULL default ''
);
CREATE TABLE NoteTexte (
  numNote integer NOT NULL default '0',
  texteNote text NOT NULL,
  idDocument integer NOT NULL default '0',
  PRIMARY KEY  (numNote,idDocument)
);
CREATE TABLE users (
  id integer PRIMARY KEY,
  username varchar(255) NOT NULL default '',
  password varchar(255) NOT NULL default ''
);
CREATE TABLE NotePossible (
  idNote integer AUTO_INCREMENT,
  numNote integer NOT NULL default '0',
  motNote varchar(255) NOT NULL default '',
  extraitMotNote text NOT NULL,
  probaNote tinyint(4) NOT NULL default '0',
  idDocument integer NOT NULL default '0',
  PRIMARY KEY  (idNote,idDocument)
);
COMMIT;

