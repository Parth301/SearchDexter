CREATE TABLE connections (
    id INT AUTO_INCREMENT PRIMARY KEY, -- Unique identifier for each row
    from_url VARCHAR(2083) NOT NULL,   -- Source URL (not null)
    to_url VARCHAR(2083) NOT NULL,     -- Destination URL (not null)
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP, -- Timestamp for record creation
    UNIQUE(from_url, to_url)           -- Enforce unique pairs of from_url and to_url
);
