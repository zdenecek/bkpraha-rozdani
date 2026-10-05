-- Run ONLY in an isolated test database with schema.sql already applied.
INSERT INTO posts(id,request_key,payload,status,published_at) VALUES(100,REPEAT('a',64),'{}','published','2026-10-04 08:00:00');
START TRANSACTION;
SELECT id FROM posts WHERE id=100 AND status='published' FOR UPDATE;
INSERT INTO poll_votes(post_id,poll_key,voter_key,option_index) VALUES(100,REPEAT('b',64),REPEAT('c',64),0) ON DUPLICATE KEY UPDATE voter_key=VALUES(voter_key);
INSERT INTO poll_votes(post_id,poll_key,voter_key,option_index) VALUES(100,REPEAT('b',64),REPEAT('c',64),1) ON DUPLICATE KEY UPDATE voter_key=VALUES(voter_key);
COMMIT;
INSERT INTO comments(post_id,request_key,author,body) VALUES(100,REPEAT('d',64),'Test','Test') ON DUPLICATE KEY UPDATE id=LAST_INSERT_ID(id);
INSERT INTO comments(post_id,request_key,author,body) VALUES(100,REPEAT('d',64),'Test','Test') ON DUPLICATE KEY UPDATE id=LAST_INSERT_ID(id);
CREATE PROCEDURE assert_community() BEGIN IF (SELECT COUNT(*) FROM poll_votes WHERE post_id=100)<>1 OR (SELECT option_index FROM poll_votes WHERE post_id=100)<>0 THEN SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT='duplicate vote changed result'; END IF; IF (SELECT COUNT(*) FROM comments WHERE post_id=100)<>1 OR (SELECT COUNT(*) FROM comments WHERE post_id=100 AND status='published')<>0 THEN SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT='comment deduplication or moderation failed'; END IF; END;
CALL assert_community();
DROP PROCEDURE assert_community;
UPDATE comments SET status='published' WHERE post_id=100 AND status='pending';
DELETE FROM posts WHERE id=100;
CREATE PROCEDURE assert_cleanup() BEGIN IF (SELECT COUNT(*) FROM poll_votes WHERE post_id=100)<>0 OR (SELECT COUNT(*) FROM comments WHERE post_id=100)<>0 THEN SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT='foreign key cascade failed'; END IF; END;
CALL assert_cleanup();
DROP PROCEDURE assert_cleanup;
