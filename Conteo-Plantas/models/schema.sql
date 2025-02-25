CREATE TABLE plant_counts (
    id INT AUTO_INCREMENT PRIMARY KEY,
    block_number INT NOT NULL,
    bed_number VARCHAR(10) NOT NULL,
    year_week VARCHAR(4) NOT NULL,
    count_date DATE NOT NULL,
    total_plants INT NOT NULL,
    total_frames INT NOT NULL,
    video_path VARCHAR(255) NOT NULL,
    processed_video_path VARCHAR(255) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

CREATE INDEX idx_block_bed ON plant_counts(block_number, bed_number);
CREATE INDEX idx_count_date ON plant_counts(count_date);