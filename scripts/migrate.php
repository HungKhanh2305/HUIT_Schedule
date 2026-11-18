<?php

if (PHP_SAPI !== "cli") {
    http_response_code(403);
    exit;
}
require dirname(__DIR__).'/app/bootstrap.php';
$queries = [
'CREATE TABLE IF NOT EXISTS rooms (id INT AUTO_INCREMENT PRIMARY KEY, branch_id INT NOT NULL, name VARCHAR(100) NOT NULL, capacity INT NULL, UNIQUE KEY branch_room(branch_id,name), FOREIGN KEY(branch_id) REFERENCES branches(id))',
'CREATE TABLE IF NOT EXISTS subjects (id INT AUTO_INCREMENT PRIMARY KEY, code VARCHAR(50) NOT NULL UNIQUE, name VARCHAR(150) NOT NULL)',
'CREATE TABLE IF NOT EXISTS terms (id INT AUTO_INCREMENT PRIMARY KEY, name VARCHAR(100) NOT NULL, start_date DATE NOT NULL, end_date DATE NOT NULL)',
'CREATE TABLE IF NOT EXISTS teacher_subjects (teacher_id INT NOT NULL, subject_id INT NOT NULL, PRIMARY KEY(teacher_id,subject_id), FOREIGN KEY(teacher_id) REFERENCES teachers(id) ON DELETE CASCADE, FOREIGN KEY(subject_id) REFERENCES subjects(id) ON DELETE CASCADE)',
'CREATE TABLE IF NOT EXISTS travel_times (branch_from INT NOT NULL, branch_to INT NOT NULL, minutes SMALLINT UNSIGNED NOT NULL, PRIMARY KEY(branch_from,branch_to), FOREIGN KEY(branch_from) REFERENCES branches(id) ON DELETE CASCADE, FOREIGN KEY(branch_to) REFERENCES branches(id) ON DELETE CASCADE)',
'CREATE TABLE IF NOT EXISTS schedule_exceptions (id INT AUTO_INCREMENT PRIMARY KEY, schedule_id INT NOT NULL, occurrence_date DATE NOT NULL, note VARCHAR(255) NOT NULL, UNIQUE KEY occurrence(schedule_id,occurrence_date), FOREIGN KEY(schedule_id) REFERENCES schedules(id) ON DELETE CASCADE)'];
foreach ($queries as $q) {
    $db->exec($q);
}
$columns = ['subject_id' => 'INT NULL','term_id' => 'INT NULL','room_id' => 'INT NULL','class_code' => 'VARCHAR(80) NULL'];
foreach ($columns as $name => $type) {
    $s = $db->prepare('SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME=\'schedules\' AND COLUMN_NAME=?');
    $s->execute([$name]);
    if (!$s->fetchColumn()) {
        $db->exec("ALTER TABLE schedules ADD COLUMN `$name` $type");
    }
}
echo "Đã cập nhật cấu trúc dữ liệu. Dữ liệu hiện có được giữ nguyên.\n";
