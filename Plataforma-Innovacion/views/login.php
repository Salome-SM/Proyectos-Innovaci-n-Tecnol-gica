<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Ideas Creativas - Login</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@600;700;800&display=swap" rel="stylesheet">
    <style>
        body {
            font-family: 'Poppins', 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            display: flex;
            justify-content: center;
            align-items: center;
            height: 100vh;
            margin: 0;
            background-image: url('images/image.jpeg');
            background-size: cover;
            background-position: center;
            background-repeat: no-repeat;
            background-attachment: fixed;
        }
        .login-container {
            display: flex;
            background-color: rgba(255, 255, 255, 0.8);
            border-radius: 20px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.1);
            overflow: hidden;
            width: 900px;
            height: 600px;
        }
        .login-form {
            flex: 1;
            padding: 30px 50px;
            display: flex;
            flex-direction: column;
            justify-content: center;
            align-items: center;
            text-align: center;
        }
        .welcome-section {
            flex: 1;
            background-color: rgba(2, 94, 115, 0.8);
            color: white;
            padding: 50px;
            display: flex;
            flex-direction: column;
            justify-content: center;
            align-items: center;
            text-align: center;
        }
        .form-control {
            border-radius: 25px;
            padding: 15px 25px;
            font-size: 16px;
            border: 2px solid #e1e5ea;
            margin-bottom: 20px;
            width: 100%;
        }
        .btn-login {
            background-color: #EC6F17;
            border: none;
            border-radius: 25px;
            padding: 15px;
            font-size: 18px;
            font-weight: bold;
            text-transform: uppercase;
            letter-spacing: 1px;
            transition: all 0.3s ease;
            color: white;
            width: 100%;
        }
        .btn-login:hover {
            background-color: #d86315;
            transform: translateY(-3px);
            box-shadow: 0 5px 15px rgba(236, 111, 23, 0.3);
        }
        .salome-text {
            font-size: 80px;
            font-weight: 800;
            font-family: 'Poppins', sans-serif;
            margin-bottom: 5px;
            background: linear-gradient(45deg, #025E73 20%, #EC6F17 40%, #4CAF50 60%, #2196F3 80%);
            -webkit-background-clip: text;
            background-clip: text;
            -webkit-text-fill-color: transparent;
            text-fill-color: transparent;
            margin-bottom: 0;
            letter-spacing: 2px;
            text-shadow: 1px 1px 2px rgba(0,0,0,0.1);
        }
        .platform-text {
            font-size: 18px;
            font-weight: 700;
            font-family: 'Poppins', sans-serif;
            color: #025E73;
            text-transform: uppercase;
            letter-spacing: 3px;
            margin-top: 50px;
            margin-bottom: 10px;
            text-shadow: 0.5px 0.5px 1px rgba(2,94,115,0.1);
        }
        .ideas-creativas {
            margin-bottom: 30px;
        }
        .ideas {
            color: #025E73;
            font-size: 36px;
            font-weight: 600;
            font-family: 'Poppins', sans-serif;
        }
        .creativas {
            color: #EC6F17;
            font-size: 36px;
            font-weight: 600;
            font-family: 'Poppins', sans-serif;
        }
        .form-section {
            width: 100%;
            max-width: 350px;
        }
        .welcome-section img {
            max-width: 80%;
            height: auto;
            margin-bottom: 30px;
            border-radius: 15px;
        }
        .header-section {
            margin-bottom: 20px;
        }
    </style>
</head>
<body>
    <div class="login-container">
        <div class="login-form">
            <div class="header-section">
                <h1 class="salome-text">SALOMÉ</h1>
                <p class=""></p>
                <p class="platform-text">Plataforma</p>
            </div>
            <div class="form-section">
                <div class="ideas-creativas">
                    <span class="ideas">Ideas</span><span class="creativas">Creativas</span>
                </div>
                <form method="POST" action="index.php?action=login">
                    <input type="text" name="emailOrUser" class="form-control" placeholder="Correo electrónico" required>
                    <input type="password" name="password" class="form-control" placeholder="Contraseña" required>
                    <button type="submit" class="btn btn-login">Ingresar</button>
                </form>
            </div>
        </div>
        <div class="welcome-section">
            <img src="images/Logo Innovacion con fondo.jpg" alt="Ideas Creativas Logo">
            <h2>¡Bienvenido!</h2>
            <p>Ingresa y comparte tus ideas creativas con nosotros</p>
        </div>
    </div>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>