<?php

session_start();
$username = " ";
$email = "";
$errors = [];
//Conexão com o banco de dados
$db = mysqli_connect('localhost', 'root', '', 'ftp');

//Quando o botão de registro for clicado
if (isset($_POST['register'])) {
    //transforma os parametros passados do metodo POST em string compatível com mysql
    $username = mysqli_real_escape_string($db, $_POST['username']);
    $email = mysqli_real_escape_string($db, $_POST['email']);
    $password_1 = mysqli_real_escape_string($db, $_POST['password_1']);
    $password_2 = mysqli_real_escape_string($db, $_POST['password_2']);
    //É necessário selecionar o banco antes de executar um comando
    mysqli_select_db($db, 'ftp');
    //Verificação para não fazer cadastro duplicado
    $sql = "SELECT * FROM `users` WHERE email='$email'";
    $con = mysqli_query($db, $sql);
    $dados = $con->fetch_array();


    //Garante que o formulário esteja preenchido corretamente

    if (empty($username)) {
        array_push($errors, "Usuário não preenchido");
    }
    if (empty($email)) {
        array_push($errors, "Email não preenchido");
    }
    if (empty($password_1)) {
        array_push($errors, "Senha não preenchida");
    }
    if (empty($password_2)) {
        array_push($errors, "Confirmação de senha não preenchida");
    }

    if ($dados['email'] == $email) {
        array_push($errors, "Já existe uma conta com esse email");
    }

    if ($password_1 != $password_2) {
        array_push($errors, "As duas senhas estão diferentes");
    }

    // Caso não tenha nenhum erro salve os dados no banco de dados
    if (count($errors) == 0) {

        //É necessário selecionar o banco antes de executar um comando
        mysqli_select_db($db, 'ftp');
        $password = md5($password_1);//Criptografar a senha antes de guardar no banco
        //Comando de inserção no banco
        $sql = "INSERT INTO users (username, passwords, email) VALUES ('$username','$password','$email') ";
        //Execução do comando em si utilizando como parâmetro a conexão
        mysqli_query($db, $sql);
        $sql = " SELECT id FROM `users` WHERE email='$email' ";
        $result = mysqli_query($db, $sql); //Pega o resultado da query para processamento
        $row = mysqli_fetch_assoc($result); //Processa o resultado da consulta em uma linha que o php transforma em array
        //Salvando os dados da sessão
        $_SESSION['id'] = $row['id'];//Seleciona a coluna dentro do array
        $_SESSION['username'] = $username;
        $_SESSION['success'] = "Você agora está logado";
        header('location: home.php'); //redireciona para a homepage

    }
}

//Login da pagina de login
if (isset($_POST['login'])) {
    $email = mysqli_real_escape_string($db, $_POST['email']);
    $password = mysqli_real_escape_string($db, $_POST['password']);

    //Garante que o formulário esteja preenchido
    if (empty($email)) {
        array_push($errors, "Email não preenchido");
    }
    if (empty($password)) {
        array_push($errors, "Senha não preenchida");
    }

    if (count($errors) == 0) {
        $password = md5($password); //Criptografa a senha digitada para comparar no banco de dados
        $query = "SELECT * FROM users WHERE email='$email' AND passwords='$password'";
        mysqli_select_db($db, 'ftp');
        $result = mysqli_query($db, $query);

        if (mysqli_num_rows($result) == 1) {
            //Entra com o email e senha
            $sql = " SELECT id FROM `users` WHERE email='$email' "; //comando para pegar o id no banco
            $result = mysqli_query($db, $sql); //Pega o resultado da query para processamento
            $row = mysqli_fetch_assoc($result); //Processa o resultado da consulta em uma linha que o php transforma em array
            $_SESSION['id'] = $row['id'];//Seleciona a coluna dentro do array
            $sql = " SELECT username FROM `users` WHERE email='$email' ";
            $result = mysqli_query($db, $sql);
            $row = mysqli_fetch_assoc($result);
            $_SESSION['username'] = $row['username'];
            $_SESSION['success'] = "Você agora está logado";
            header('location: home.php'); //redireciona para a homepage

        } else {
            array_push($errors, "Email ou senha incorreta");
        }
    }

}



//logout
if (isset($_GET['logout'])) {
    session_destroy();
    unset($_SESSION['id']);
    unset($_SESSION['username']);
    header('location: index.php');
}

//Upload de arquivos quando o botão for pressionado
if (isset($_POST['submit'])) {
    //nome do arquivo
    $file = $_FILES['file']['name'];
    //Garante que tenha algum arquivo
    if (empty($file)) {
        array_push($errors, "Nenhum arquivo para fazer upload");
    }
    if (count($errors) == 0) {
        //Mover o arquivo que foi feito o upload para uma pasta
        move_uploaded_file($_FILES['file']['tmp_name'], "files/$file");
        //Nome do diretório
        $path = $_SERVER['DOCUMENT_ROOT']."/openftp/"."files/".$file;
        $id = $_SESSION['id'];
        //Inserção do nome e do caminho no banco
        $sql = "INSERT INTO `files` (name,path,id_user) VALUES ('$file','$path','$id')";
        mysqli_select_db($db, 'ftp');
        mysqli_query($db, $sql);
    }


}

//Excluir arquivos
if (isset($_GET['excluir'])) {
    $cod_arquivo = $_GET['excluir'];
    $sql = "DELETE FROM `files` WHERE `files`.`id` = '$cod_arquivo'";
    mysqli_select_db($db, 'ftp');
    mysqli_query($db, $sql);

}

//Editar arquivos
if (isset($_POST['confirmar'])) {
    $cod_arquivo = $_POST['codigo'];
    $nome_arquivo = $_POST['file'];
    $nome_antigo = $_POST['file_past'];
    $path = $_SERVER['DOCUMENT_ROOT']."/openftp/"."files/".$nome_antigo;
    $path_novo = $_SERVER['DOCUMENT_ROOT']."/openftp/"."files/".$nome_arquivo;
    //Muda o nome do arquivo



    //Garante que o formulário esteja preenchido
    if (empty($cod_arquivo)) {
        array_push($errors, "Arquivo invalido");
    }
    if (empty($nome_arquivo)) {
        array_push($errors, "Nome não preenchido");
    }
    if (empty($nome_antigo)) {
        array_push($errors, "Arquivo invalido");
    }
    //Caso não haja erros salva o novo nome no banco
    if (count($errors) == 0) {
        rename($path, $path_novo);
        $sql = "UPDATE `files` SET name='$nome_arquivo' WHERE `files`.`id` = '$cod_arquivo'";
        mysqli_select_db($db, 'ftp');
        mysqli_query($db, $sql);

        $sql = "UPDATE `files` SET path='$path_novo' WHERE `files`.`id` = '$cod_arquivo'";
        mysqli_query($db, $sql);
        header('location: home.php'); //redireciona para a homepage
    }
}
