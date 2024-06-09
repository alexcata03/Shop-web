<?php
declare(strict_types=1);

namespace App\Controllers;

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Slim\Views\Twig;
use PDO;
use \Firebase\JWT\JWT;

class UserController
{
    private PDO $db;
    private Twig $view;
    private string $secretKey = 'your_secret_key';

    public function __construct(PDO $db, Twig $view)
    {
        $this->db = $db;
        $this->view = $view;
    }
    
    // Generate JWT token
    private function generateJWT($userId, $username)
{
    $payload = [
        'user_id' => $userId,
        'username' => $username,
        'exp' => time() + (14 * 24 * 60 * 60) // Token expiration time (14 days)
    ];
    $jwt = JWT::encode($payload, $this->secretKey, 'HS256');
    return $jwt;
}

 /// Login
public function login(Request $request, Response $response, $args)
{
    // Retrieve email and password from the request
    $loginData = $request->getParsedBody();
    $email = isset($loginData['email']) ? $loginData['email'] : null;
    $password = isset($loginData['password']) ? $loginData['password'] : null;

    // Check if both email and password are provided
    if (!$email || !$password) {
        error_log('Error: Email and password are required');
        $response->getBody()->write(json_encode([
            'error' => 'Email and password are required',
            'email' => $email,
            'password' => $password
        ]));
        return $response->withStatus(400)->withHeader('Content-Type', 'application/json');
    }

    // Perform database query to fetch user data based on email
    $stmt = $this->db->prepare('SELECT * FROM users WHERE email = ? LIMIT 1');
    $stmt->execute([$email]);
    $user = $stmt->fetch();

    // Check if user exists
    if (!$user) {
        error_log('Error: Invalid email');
        $response->getBody()->write(json_encode([
            'error' => 'Invalid email',
            'email' => $email,
            'password' => $password
        ]));
        return $response->withStatus(401)->withHeader('Content-Type', 'application/json');
    }

    // Get the hashed password from the database
    $passwordFromDb = $user['password'];
    // Manually verify the password
    if ($password !== $passwordFromDb) {
        // Passwords don't match, render error message
        error_log('Error: Invalid password');
        $response->getBody()->write(json_encode([
            'error' => 'Invalid password',
            'email' => $email,
            'password' => $password
        ]));
        return $response->withStatus(401)->withHeader('Content-Type', 'application/json');
    }

    // Generate JWT token
    $token = $this->generateJWT($user['id'], $user['email']);

    // Redirect to dashboard on successful login
    $response->getBody()->write(json_encode([
        'userId' => $user['id'], // Pass the user ID to the template
        'email' => $email,
        'token' => $token
    ]));
    return $response->withHeader('Content-Type', 'application/json');
}


// Method for user registration
public function createUser(Request $request, Response $response, $args)
{
    $registerData = $request->getParsedBody();

    // Extract registration data
    $username = $registerData['username'];
    $email = $registerData['email'];
    $password = $registerData['password'];
    $phone = $registerData['phone'];
    $address = $registerData['address'];
    $firstName = $registerData['firstName'];
    $lastName = $registerData['lastName'];

    // Check if username already exists
    $stmt = $this->db->prepare('SELECT COUNT(*) AS count FROM users WHERE username = ?');
    $stmt->execute([$username]);
    $resultUsername = $stmt->fetch();

    // Check if email already exists
    $stmt = $this->db->prepare('SELECT COUNT(*) AS count FROM users WHERE email = ?');
    $stmt->execute([$email]);
    $resultEmail = $stmt->fetch();

    if ($resultUsername['count'] > 0) {
        // Username already exists, return an error
        $error = 'Username already exists in the database';
        $response->getBody()->write(json_encode(['error' => $error]));
        return $response->withStatus(400)->withHeader('Content-Type', 'application/json');
    }

    if ($resultEmail['count'] > 0) {
        // Email already exists, return an error
        $error = 'Email already exists in the database';
        $response->getBody()->write(json_encode(['error' => $error]));
        return $response->withStatus(400)->withHeader('Content-Type', 'application/json');
    }

    // If no errors, proceed with creating the user

    // Prepare and execute the SQL query to insert the new user
    $stmt = $this->db->prepare('INSERT INTO users (username, email, password, phone, address, userStatus, firstName, lastName) VALUES (?, ?, ?, ?, ?, 1, ?, ?)');
    $stmt->execute([$username, $email, $password, $phone, $address, $firstName, $lastName]);

    // Get the user ID of the newly created user
    $userId = $this->db->lastInsertId();
    
    // Generate JWT token
    $token = $this->generateJWT($userId, $username);

    // Return success response with JWT token
    $response->getBody()->write(json_encode([
        'message' => 'User created successfully',
        'token' => $token
    ]));
    return $response->withStatus(201)->withHeader('Content-Type', 'application/json');
}



public function logout(Request $request, Response $response, $args)
{
    // Assuming you clear the JWT token from the client-side
    $response->getBody()->write(json_encode([
        'message' => 'Logged out successfully'
    ]));
    return $response->withHeader('Content-Type', 'application/json');
}

public function getAll(Request $request, Response $response, $args)
{
    // Retrieve user ID from JWT token
    $token = $request->getAttribute('token');
    $userId = $token['id'];

    // Get the user status from the database
    $stmt = $this->db->prepare('SELECT userStatus FROM users WHERE id = ?');
    $stmt->execute([$userId]);
    $userStatus = $stmt->fetchColumn();

    // Check if the user has permission to view all users
    if ($userStatus != 2) {
        $response->getBody()->write(json_encode([
            'error' => 'You do not have permission to view all users'
        ]));
        return $response->withStatus(403)->withHeader('Content-Type', 'application/json');
    }

    // Fetch all users from the database
    $stmt = $this->db->query('SELECT * FROM users');
    $users = $stmt->fetchAll();

    // Return the users as JSON
    $response->getBody()->write(json_encode($users));
    return $response->withHeader('Content-Type', 'application/json');
}

public function getUserByUsername(Request $request, Response $response, $args)
{
    // Retrieve user ID from JWT token if available
    $token = $request->getAttribute('token');
    $userId = null;
    $userStatus = null;
    
    if ($token !== null && is_array($token)) {
        $userId = $token['id'];

        // Get the user status from the database
        $stmt = $this->db->prepare('SELECT userStatus FROM users WHERE id = ?');
        $stmt->execute([$userId]);
        $userStatus = $stmt->fetchColumn();
    }

    // Check if the user has permission to view other users
    if ($userStatus !== 2) {
        // Return an error response if user does not have permission
        $responseBody = json_encode(['error' => 'You do not have permission to view other users']);
        $response = $response->withStatus(403)->withHeader('Content-Type', 'application/json')->getBody()->write($responseBody);
        return $response;
    }

    // Get the username from the URL parameters
    $username = $args['username'];

    // Prepare and execute the SQL query to select user data by username
    $stmt = $this->db->prepare('SELECT * FROM users WHERE username = ?');
    $stmt->execute([$username]);
    $user = $stmt->fetch();

    // Check if user exists
    if (!$user) {
        // User not found, return an error message
        $responseBody = json_encode(['error' => 'User not found']);
        $response = $response->withStatus(404)->withHeader('Content-Type', 'application/json')->getBody()->write($responseBody);
        return $response;
    }

    // Include user ID and username of the connected user in the response if available
    if ($userId !== null) {
        $user['connectedUserId'] = $userId;
        $user['connectedUsername'] = $token['username'];
    }

    // User found, return the user information as JSON
    $responseBody = json_encode($user);
    $response = $response->withStatus(200)->withHeader('Content-Type', 'application/json')->getBody()->write($responseBody);
    return $response;
}




// Method to update user information based on username
public function updateUserById(Request $request, Response $response, $args)
{
    // Retrieve user ID from JWT token
    $token = $request->getAttribute('token');
    $userId = $token['id'];

    // Get the user status from the database
    $stmt = $this->db->prepare('SELECT userStatus FROM users WHERE id = ?');
    $stmt->execute([$userId]);
    $userStatus = $stmt->fetchColumn();

    // Check if the user has permission to update other users
    if ($userStatus != 2) {
        $response->getBody()->write(json_encode([
            'error' => 'You do not have permission to update other users'
        ]));
        return $response->withStatus(403)->withHeader('Content-Type', 'application/json');
    }

    // Get the user ID from the URL parameters
    $id = $args['id'];

    // Retrieve updated user data from the request body
    $userData = $request->getParsedBody();

    // Check if $userData is null or not an array
    if ($userData === null || !is_array($userData)) {
        $response->getBody()->write(json_encode([
            'error' => 'Invalid data sent for update'
        ]));
        return $response->withStatus(400)->withHeader('Content-Type', 'application/json');
    }

    // Initialize arrays to hold placeholders and values for the SQL query
    $placeholders = [];
    $values = [];

    // Iterate over the fields in $userData
    foreach ($userData as $field => $value) {
        // Check if the field is valid and not empty
        if (!empty($value)) {
            // Add the field to the placeholders array
            $placeholders[] = "$field = ?";
            // Add the value to the values array
            $values[] = $field === 'password' ? password_hash($value, PASSWORD_BCRYPT) : $value;
        }
    }

    // Check if any fields were provided for update
    if (empty($placeholders)) {
        // No fields provided for update, return an error
        $response->getBody()->write(json_encode([
            'error' => 'No fields provided for update'
        ]));
        return $response->withStatus(400)->withHeader('Content-Type', 'application/json');
    }

    // Prepare the SQL query with dynamic placeholders
    $sql = 'UPDATE users SET ' . implode(', ', $placeholders) . ' WHERE id = ?';
    $values[] = $id;

    // Execute the SQL query
    $stmt = $this->db->prepare($sql);
    $stmt->execute($values);

    // Return a success message
    $response->getBody()->write(json_encode([
        'message' => 'User updated successfully'
    ]));
    return $response->withHeader('Content-Type', 'application/json');
}


// Method to delete user based on username
public function deleteUserByUsername(Request $request, Response $response, $args)
{
    // Retrieve user ID from JWT token
    $token = $request->getAttribute('token');
    $userId = $token['id'];

    // Get the user status from the database
    $stmt = $this->db->prepare('SELECT userStatus FROM users WHERE id = ?');
    $stmt->execute([$userId]);
    $userStatus = $stmt->fetchColumn();

    // Check if the user has permission to delete other users
    if ($userStatus != 2) {
        $response->getBody()->write(json_encode([
            'error' => 'You do not have permission to delete other users'
        ]));
        return $response->withStatus(403)->withHeader('Content-Type', 'application/json');
    }

    // Get the username from the URL parameters
    $username = $args['username'];

    // Prepare and execute the SQL query to delete the user
    $stmt = $this->db->prepare('DELETE FROM users WHERE username = ?');
    $stmt->execute([$username]);

    // Check if any rows were affected by the delete operation
    $rowCount = $stmt->rowCount();
    if ($rowCount == 0) {
        // No user was deleted, return an error
        $response->getBody()->write(json_encode([
            'error' => 'User not found or could not be deleted'
        ]));
        return $response->withStatus(404)->withHeader('Content-Type', 'application/json');
    }

    // User deleted successfully, return a success message
    $response->getBody()->write(json_encode([
        'message' => 'User deleted successfully'
    ]));
    return $response->withHeader('Content-Type', 'application/json');
}



}
?>