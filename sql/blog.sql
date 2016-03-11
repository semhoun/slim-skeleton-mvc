PRAGMA foreign_keys=OFF;
BEGIN TRANSACTION;
CREATE TABLE post (id int primary key not null, title char(100) default null, slug char(200) not null, content text not null);
INSERT INTO "post" VALUES(1,'First blog post','first-blog-post','This is sample blog post. If you see this content, doctrine is working fine.');
COMMIT;
