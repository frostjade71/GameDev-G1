-- Add username columns to friend_requests table
ALTER TABLE friend_requests
ADD COLUMN requester_username VARCHAR(255) AFTER requester_id,
ADD COLUMN receiver_username VARCHAR(255) AFTER receiver_id;

-- Update existing records with usernames
UPDATE friend_requests fr
JOIN users u1 ON fr.requester_id = u1.id
JOIN users u2 ON fr.receiver_id = u2.id
SET fr.requester_username = u1.username,
    fr.receiver_username = u2.username;

-- Add foreign key constraints for the username columns
ALTER TABLE friend_requests
ADD CONSTRAINT fk_requester_username FOREIGN KEY (requester_username) REFERENCES users(username) ON UPDATE CASCADE,
ADD CONSTRAINT fk_receiver_username FOREIGN KEY (receiver_username) REFERENCES users(username) ON UPDATE CASCADE;

-- Update the send_friend_request.php to include usernames when creating new requests
-- This will be handled in the PHP code, so no SQL needed here
