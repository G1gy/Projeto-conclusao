<?php
// Inicia a conexão com o banco de dados
require_once "conector_db.php";

// Ativa a exibição de erros para depuração
error_reporting(E_ALL);
ini_set('display_errors', 1);

header('Content-Type: application/json'); // Define o tipo de resposta como JSON

$response = array('success' => false, 'message' => '');

// Função para gerar um número de matrícula aleatório único
function generateUniqueMatricula($conn) {
    $matricula = null;
    $count = 0;
    do {
        // Gera um número aleatório de 4 dígitos
        $matricula = sprintf('%04d', rand(0, 9999));

        // Verifica se o número já existe no banco de dados
        $sql = "SELECT COUNT(*) FROM tb_funcionario WHERE matricula = ?";
        if ($stmt = $conn->prepare($sql)) {
            $stmt->bind_param("s", $matricula);
            $stmt->execute();
            $stmt->bind_result($count);
            $stmt->fetch();
            $stmt->close();
        } else {
            die("Erro na preparação da consulta: " . $conn->error);
        }
        
    } while ($count > 0); // Garante que o número seja único

    return $matricula;
}

// Gera um número de matrícula único
$matricula = generateUniqueMatricula($conn);

// Captura e sanitiza o nome (campo obrigatório)
$username = !empty($_POST['username']) ? mysqli_real_escape_string($conn, $_POST['username']) : null;

if (empty($username)) {
    $response['message'] = "O nome é obrigatório!";
    echo json_encode($response);
    exit();
}

// Captura e sanitiza os campos obrigatórios
$email = !empty($_POST['email']) ? mysqli_real_escape_string($conn, $_POST['email']) : null;
$password = !empty($_POST['password']) ? mysqli_real_escape_string($conn, $_POST['password']) : null;

if (empty($email) || empty($password)) {
    $response['message'] = "Email e senha são obrigatórios!";
    echo json_encode($response);
    exit();
}

// Captura e sanitiza os campos opcionais
$gender = !empty($_POST['gender']) ? mysqli_real_escape_string($conn, $_POST['gender']) : null;
$dob = !empty($_POST['dob']) ? mysqli_real_escape_string($conn, $_POST['dob']) : null;
$position = !empty($_POST['position']) ? mysqli_real_escape_string($conn, $_POST['position']) : null;
$access_level = !empty($_POST['access-level']) ? mysqli_real_escape_string($conn, $_POST['access-level']) : null;
$telefone = !empty($_POST['telefone']) ? mysqli_real_escape_string($conn, $_POST['telefone']) : null;

// Hash da senha
$hashed_password = password_hash($password, PASSWORD_DEFAULT);

// Tratamento do upload da foto (opcional)
$photo_folder = 'images/template.png'; // Foto padrão se não for fornecida
if (!empty($_FILES['foto']['name'])) {
    $photo = $_FILES['foto']['name'];
    $photo_tmp = $_FILES['foto']['tmp_name'];
    $photo_folder = "../uploads/" . basename($photo);

    // Verifica o tipo de arquivo
    $allowed_types = array('jpg', 'jpeg', 'png', 'gif');
    $photo_ext = pathinfo($photo, PATHINFO_EXTENSION);

    if (!in_array(strtolower($photo_ext), $allowed_types)) {
        $response['message'] = "Tipo de arquivo não permitido. Por favor, envie uma foto em JPG, JPEG, PNG ou GIF.";
        echo json_encode($response);
        exit();
    }

    if (!move_uploaded_file($photo_tmp, $photo_folder)) {
        $response['message'] = "Falha ao fazer o upload da foto.";
        echo json_encode($response);
        exit();
    }
}

// Query de inserção no banco de dados usando prepared statement
$sql = "INSERT INTO tb_funcionario (matricula, nome, email, senha, genero, data_nascimento, cargo, nivel_acesso, telefone, foto) 
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";

// Preparar a instrução SQL
$stmt = $conn->prepare($sql);

if (!$stmt) {
    $response['message'] = "Erro na preparação da consulta: " . $conn->error;
    echo json_encode($response);
    exit();
}

// Bind os parâmetros, alguns deles podem ser nulos
$stmt->bind_param("ssssssssss", $matricula, $username, $email, $hashed_password, $gender, $dob, $position, $access_level, $telefone, $photo_folder);

if ($stmt->execute()) {
    $response['success'] = true;
    $response['message'] = 'Funcionário cadastrado com sucesso!';
} else {
    $response['message'] = 'Erro ao cadastrar funcionário: ' . $stmt->error;
}

echo json_encode($response);

$stmt->close();
$conn->close();
?>
