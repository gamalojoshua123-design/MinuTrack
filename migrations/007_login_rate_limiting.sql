-- Migration 007: Server-side login rate limiting
-- Replaces the session-based login attempt counter (bypassable by discarding
-- the session cookie) with a table keyed by client IP.

CREATE TABLE IF NOT EXISTS login_rate_limits (
    ip_address VARCHAR(45) NOT NULL PRIMARY KEY,
    attempt_count INT NOT NULL DEFAULT 0,
    first_attempt_at DATETIME NOT NULL,
    updated_at DATETIME NOT NULL
);
