<?php

require dirname(__DIR__).'/bootstrap.php';
requireAdmin();
$type = $_GET['type'] ?? 'teachers';
$types = ['teachers' => 'Giảng viên','branches' => 'Cơ sở','rooms' => 'Phòng học','subjects' => 'Học phần','terms' => 'Học kỳ','travel' => 'Thời gian di chuyển','assignments' => 'Chuyên môn'];
if (!isset($types[$type])) {
    $type = 'teachers';
} $error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf();
    try {
        $action = $_POST['action'] ?? '';
        $id = (int)($_POST['id'] ?? 0);
        if ($action === 'delete') {
            $tables = ['teachers' => 'teachers','branches' => 'branches','rooms' => 'rooms','subjects' => 'subjects','terms' => 'terms'];
            if (!isset($tables[$type])) {
                throw new Exception('Không thể xóa mục này.');
            }$db->prepare('DELETE FROM '.$tables[$type].' WHERE id=?')->execute([$id]);
        } elseif ($action === 'save') {
            $v = fn ($key) => trim((string)($_POST[$key] ?? ''));
            switch ($type) {
                case 'teachers': $name = $v('full_name');
                    $email = $v('email');
                    $phone = $v('phone');
                    $expertise = $v('expertise');
                    $password = $v('password');
                    if (!$name || !filter_var($email, FILTER_VALIDATE_EMAIL) || !$expertise || (!$id && strlen($password) < 8) || ($password !== '' && strlen($password) < 8)) {
                        throw new Exception('Nhập tên, email hợp lệ, chuyên môn và mật khẩu ít nhất 8 ký tự cho tài khoản mới.');
                    } if ($id) {
                        $db->prepare('UPDATE teachers SET full_name=?,email=?,phone=?,expertise=? WHERE id=?')->execute([$name,$email,$phone,$expertise,$id]);
                        if ($password !== '') {
                            $db->prepare('UPDATE teachers SET password_hash=? WHERE id=?')->execute([password_hash($password, PASSWORD_DEFAULT),$id]);
                        }
                    } else {
                        $db->prepare('INSERT INTO teachers(full_name,email,phone,expertise,password_hash) VALUES(?,?,?,?,?)')->execute([$name,$email,$phone,$expertise,password_hash($password, PASSWORD_DEFAULT)]);
                    }break;
                case 'branches': if (!$v('branch_name') || !$v('address')) {
                    throw new Exception('Nhập tên và địa chỉ cơ sở.');
                } if ($id) {
                    $db->prepare('UPDATE branches SET branch_name=?,address=? WHERE id=?')->execute([$v('branch_name'),$v('address'),$id]);
                } else {
                    $db->prepare('INSERT INTO branches(branch_name,address) VALUES(?,?)')->execute([$v('branch_name'),$v('address')]);
                }break;
                case 'rooms': if (!(int)$v('branch_id') || !$v('name')) {
                    throw new Exception('Chọn cơ sở và nhập tên phòng.');
                } $capacity = $v('capacity') === '' ? null : (int)$v('capacity');
                    if ($capacity !== null && $capacity < 1) {
                        throw new Exception('Sức chứa phải lớn hơn 0.');
                    }if ($id) {
                        $db->prepare('UPDATE rooms SET branch_id=?,name=?,capacity=? WHERE id=?')->execute([(int)$v('branch_id'),$v('name'),$capacity,$id]);
                    } else {
                        $db->prepare('INSERT INTO rooms(branch_id,name,capacity) VALUES(?,?,?)')->execute([(int)$v('branch_id'),$v('name'),$capacity]);
                    }break;
                case 'subjects': if (!$v('code') || !$v('name')) {
                    throw new Exception('Nhập mã và tên học phần.');
                }if ($id) {
                    $db->prepare('UPDATE subjects SET code=?,name=? WHERE id=?')->execute([$v('code'),$v('name'),$id]);
                } else {
                    $db->prepare('INSERT INTO subjects(code,name) VALUES(?,?)')->execute([$v('code'),$v('name')]);
                }break;
                case 'terms': if (!$v('name') || !$v('start_date') || !$v('end_date') || $v('start_date') > $v('end_date')) {
                    throw new Exception('Nhập học kỳ và khoảng ngày hợp lệ.');
                }if ($id) {
                    $db->prepare('UPDATE terms SET name=?,start_date=?,end_date=? WHERE id=?')->execute([$v('name'),$v('start_date'),$v('end_date'),$id]);
                } else {
                    $db->prepare('INSERT INTO terms(name,start_date,end_date) VALUES(?,?,?)')->execute([$v('name'),$v('start_date'),$v('end_date')]);
                }break;
                case 'travel': $a = (int)$v('branch_from');
                    $b = (int)$v('branch_to');
                    $m = filter_var($v('minutes'), FILTER_VALIDATE_INT);
                    if (!$a || !$b || $a === $b || $m === false || $m < 0 || $m > 1440) {
                        throw new Exception('Chọn hai cơ sở khác nhau và số phút hợp lệ.');
                    }$db->prepare('INSERT INTO travel_times(branch_from,branch_to,minutes) VALUES(?,?,?),(?,?,?) ON DUPLICATE KEY UPDATE minutes=VALUES(minutes)')->execute([$a,$b,$m,$b,$a,$m]);
                    break;
                case 'assignments': $db->prepare('INSERT IGNORE INTO teacher_subjects(teacher_id,subject_id) VALUES(?,?)')->execute([(int)$v('teacher_id'),(int)$v('subject_id')]);
                    break;
            }
        } elseif ($action === 'remove_assignment' && $type === 'assignments') {
            $db->prepare('DELETE FROM teacher_subjects WHERE teacher_id=? AND subject_id=?')->execute([(int)$_POST['teacher_id'],(int)$_POST['subject_id']]);
        }
        $_SESSION['notice'] = 'Đã lưu thay đổi.';
        go('manage.php?type='.$type);
    } catch (Throwable $e) {
        $error = $e instanceof PDOException ? 'Không thể lưu hoặc xóa: dữ liệu trùng, không hợp lệ hoặc đang được sử dụng.' : $e->getMessage();
    }
}
$branches = all($db, 'SELECT * FROM branches ORDER BY branch_name');
$teachers = all($db, 'SELECT id,full_name FROM teachers ORDER BY full_name');
$subjects = all($db, 'SELECT * FROM subjects ORDER BY name');
$queries = ['teachers' => 'SELECT id,full_name,email,phone,expertise FROM teachers ORDER BY full_name','branches' => 'SELECT * FROM branches ORDER BY branch_name','rooms' => 'SELECT rooms.*,branches.branch_name FROM rooms JOIN branches ON branches.id=rooms.branch_id ORDER BY branches.branch_name,rooms.name','subjects' => 'SELECT * FROM subjects ORDER BY name','terms' => 'SELECT * FROM terms ORDER BY start_date DESC','travel' => 'SELECT t.*,a.branch_name AS origin,b.branch_name AS destination FROM travel_times t JOIN branches a ON a.id=t.branch_from JOIN branches b ON b.id=t.branch_to WHERE t.branch_from<t.branch_to','assignments' => 'SELECT ts.*,t.full_name,s.name FROM teacher_subjects ts JOIN teachers t ON t.id=ts.teacher_id JOIN subjects s ON s.id=ts.subject_id ORDER BY t.full_name'];
$rows = all($db, $queries[$type]);
$edit = [];
if (isset($_GET['edit'])) {
    foreach ($rows as $row) {
        if (($row['id'] ?? 0) == (int)$_GET['edit']) {
            $edit = $row;
        }
    }
}
function field(string $name, string $label, string $type = 'text', bool $required = true): void
{
    global $edit;
    echo '<label>'.h($label).'<input type="'.h($type).'" name="'.h($name).'" value="'.h($edit[$name] ?? '').'" '.($required ? 'required' : '').'></label>';
}
function choose(string $name, string $label, array $rows, string $text): void
{
    global $edit;
    echo '<label>'.h($label).'<select name="'.h($name).'" required><option value="">Chọn '.$label.'</option>';
    foreach ($rows as $r) {
        echo '<option value="'.h($r['id']).'" '.(($edit[$name] ?? '') == $r['id'] ? 'selected' : '').'>'.h($r[$text]).'</option>';
    }echo '</select></label>';
}

require dirname(__DIR__).'/views/manage.php';
