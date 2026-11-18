<?php

declare(strict_types=1);
if (PHP_SAPI !== "cli") {
    http_response_code(403);
    exit;
}
require dirname(__DIR__) . '/vendor/autoload.php';
Dotenv\Dotenv::createImmutable(dirname(__DIR__))->safeLoad();
$db = new PDO('mysql:host='.($_ENV['DB_HOST'] ?? '127.0.0.1').';port='.($_ENV['DB_PORT'] ?? '3306').';dbname='.($_ENV['DB_NAME'] ?? 'huit_db').';charset=utf8mb4', $_ENV['DB_USER'] ?? 'root', $_ENV['DB_PASSWORD'] ?? '', [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
if ($db->query('SELECT COUNT(*) FROM admins')->fetchColumn() || $db->query('SELECT COUNT(*) FROM teachers')->fetchColumn() || $db->query('SELECT COUNT(*) FROM branches')->fetchColumn() || $db->query('SELECT COUNT(*) FROM schedules')->fetchColumn()) {
    exit("Cơ sở dữ liệu đã có dữ liệu; không tự động ghi đè.\n");
}
$faker = Faker\Factory::create('vi_VN');
$password = password_hash('demo1234', PASSWORD_DEFAULT);
$db->beginTransaction();
try {
    $db->prepare('INSERT INTO admins(username,password_hash) VALUES (?,?)')->execute(['admin',$password]);
    $branch = $db->prepare('INSERT INTO branches(branch_name,address) VALUES (?,?)');
    foreach ([['Cơ sở Tân Phú','140 Lê Trọng Tấn, Tân Phú, TP. HCM'],['Cơ sở Thủ Đức','TP. Thủ Đức, TP. HCM'],['Trung tâm thực hành','Quận 12, TP. HCM']] as $b) {
        $branch->execute($b);
    }
    $insert = $db->prepare('INSERT INTO teachers(full_name,email,phone,expertise,password_hash) VALUES (?,?,?,?,?)');
    $specialties = ['Lập trình PHP, Công nghệ thông tin','Cơ sở dữ liệu, Công nghệ thông tin','Mạng máy tính, Công nghệ thông tin','Toán, Toán ứng dụng','Tiếng Anh'];
    foreach ($specialties as $i => $expertise) {
        $insert->execute([$faker->name(), 'giangvien'.($i + 1).'@huit.edu.vn',$faker->phoneNumber(),$expertise,$password]);
    }
    $insert = $db->prepare('INSERT INTO schedules(teacher_id,branch_id,subject_name,room,day_of_week,start_period,end_period,start_date,end_date) VALUES (?,?,?,?,?,?,?,?,?)');
    $year = (int)date('Y');
    foreach ([[1,1,'Lập trình PHP','A101',2,1,3],[2,1,'Cơ sở dữ liệu','B202',3,4,6],[3,2,'Mạng máy tính','C105',4,7,9],[4,3,'Toán ứng dụng','D101',5,1,3]] as $row) {
        $insert->execute([...$row,"$year-01-01","$year-12-31"]);
    } $db->commit();
    echo "Đã tạo dữ liệu. Admin: admin / demo1234. Giảng viên: giangvien1@huit.edu.vn / demo1234\n";
} catch (Throwable $e) {
    $db->rollBack();
    throw $e;
}
