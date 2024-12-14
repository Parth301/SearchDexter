CREATE TABLE websites (
    id INT AUTO_INCREMENT PRIMARY KEY, -- Unique identifier for each row
    url VARCHAR(2083) NOT NULL UNIQUE, -- URL of the website (unique and not null)
    title VARCHAR(255) NOT NULL,       -- Title of the website (max length 255)
    keywords TEXT,                     -- Keywords associated with the website
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP -- Timestamp for record creation
);
