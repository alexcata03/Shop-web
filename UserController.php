<?php
declare(strict_types=1);

namespace App\Controllers;

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Slim\Views\Twig;
use PDO;
use Firebase\JWT\JWT;
use DateTimeImmutable;

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
    private function generateJWT($userId, $username, $userStatus): string
{
    $payload = [
        'iss' => 'your_app', // set issuer
        'aud' => 'your_app', // set audience
        'jti' => (string)$userId, // set token id
        'iat' => time(), // set issued at time
        'exp' => time() + (14 * 24 * 60 * 60), // set expiration time
        'user_id' => $userId, // add custom claim
        'username' => $username, // add custom claim
        'user_status' => $userStatus, // add custom claim
    ];

    return JWT::encode($payload, $this->secretKey, 'HS256');
}
 /// Login
 public function login(Request $request, Response $response, $args)
{
    $loginData = $request->getParsedBody();

    error_log('Received login data:');
    error_log(var_export($loginData, true));

    if ($loginData === null || !isset($loginData['email']) || !isset($loginData['password'])) {
        $response->getBody()->write(json_encode(['error' => 'Email and password are required']));
        return $response->withStatus(400)->withHeader('Content-Type', 'application/json');
    }

    $email = $loginData['email'];
    $password = $loginData['password'];

    $stmt = $this->db->prepare('SELECT * FROM users WHERE email = ? LIMIT 1');
    $stmt->execute([$email]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$user) {
        error_log('Error: Invalid email');
        $response->getBody()->write(json_encode(['error' => 'Invalid email']));
        return $response->withStatus(401)->withHeader('Content-Type', 'application/json');
    }

    $passwordFromDb = $user['password'];
    error_log('Stored password: ' . $passwordFromDb);

    if ($password !== $passwordFromDb) {
        error_log('Error: Invalid password');
        $response->getBody()->write(json_encode(['error' => 'Invalid password']));
        return $response->withStatus(401)->withHeader('Content-Type', 'application/json');
    }

    // Retrieve user status from the database
    $userStatus = $user['userStatus']; // Assuming 'userStatus' is the column name in the database

    // Generate JWT token with user ID, username, and user status
    $token = $this->generateJWT($user['id'], $user['username'], $userStatus);

    // Set JWT token in a cookie
    $response = $response->withHeader('Set-Cookie', "jwt={$token}; Path=/; HttpOnly; Secure; SameSite=Strict");

    $response->getBody()->write(json_encode([
        'userId' => $user['id'],
        'email' => $email,
        'token' => $token
    ]));
    return $response->withHeader('Content-Type', 'application/json');
}

public function createUser(Request $request, Response $response, $args)
{
    // Get registration data from the request body
    $registerData = $request->getParsedBody();

    // Extract registration data including username
    $email = $registerData['email'] ?? '';
    $password = $registerData['password'] ?? '';
    $firstName = $registerData['firstName'] ?? '';
    $lastName = $registerData['lastName'] ?? '';
    $username = $registerData['username'] ?? '';

    // Check if any required field is missing
    if (!$email || !$password || !$firstName || !$lastName || !$username) {
        $error = 'Email, password, first name, last name, and username are required for registration';
        $response->getBody()->write(json_encode(['error' => $error]));
        return $response->withStatus(400)->withHeader('Content-Type', 'application/json');
    }

    // Check if username already exists
    $stmt = $this->db->prepare('SELECT COUNT(*) AS count FROM users WHERE username = ?');
    $stmt->execute([$username]);
    $result = $stmt->fetch();

    if ($result['count'] > 0) {
        $error = 'Username already exists';
        $response->getBody()->write(json_encode(['error' => $error]));
        return $response->withStatus(400)->withHeader('Content-Type', 'application/json');
    }

    // If no errors, proceed with creating the user

    // Prepare and execute the SQL query to insert the new user
    $stmt = $this->db->prepare('INSERT INTO users (email, password, firstName, lastName, username,userStatus) VALUES (?, ?, ?, ?, ?,1)');
    $stmt->execute([$email, $password, $firstName, $lastName, $username]);

    // Get the user ID of the newly created user
    $userId = $this->db->lastInsertId();

    // Retrieve user status from the database
    $userStatus = 1; // Assuming 'userStatus' is set to 1 by default for new users

    // Generate JWT token with user ID, email, and user status
    $token = $this->generateJWT($userId, $email, $userStatus);

    // Return success response with JWT token
    $response->getBody()->write(json_encode([
        'message' => 'User created successfully',
        'userId' => $userId,
        'email' => $email,
        'token' => $token
    ]));
    return $response->withHeader('Content-Type', 'application/json')->withStatus(201);
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
    // Retrieve JWT token from cookies
    $cookies = $request->getCookieParams();
    $token = $cookies['jwt'] ?? null;
    print_r('text',$token);
    // Check if token is present
    if (!$token) {
        // Return an error response if token is missing
        $responseBody = json_encode(['error' => 'JWT token is missing']);
        return $response->withStatus(401)->withHeader('Content-Type', 'application/json')->getBody()->write($responseBody);
    }
    try {
        // Verify and decode JWT token
        $decodedToken = JWT::decode($token, $this->secretKey);

        // Extract user ID from decoded token
        $userId = $decodedToken->user_id;

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
            return $response->withStatus(404)->withHeader('Content-Type', 'application/json')->getBody()->write($responseBody);
        }

        // Include all required user data in the response
        $userData = [
            'username' => $user['username'],
            'email' => $user['email'],
            'phone' => $user['phone'],
            'address' => $user['address'],
            'firstName' => $user['first_name'],
            'lastName' => $user['last_name'],
            // Add more fields if needed
        ];

        // User found, return the user information as JSON
        $responseBody = json_encode($userData);
        return $response->withStatus(200)->withHeader('Content-Type', 'application/json')->getBody()->write($responseBody);
    } catch (\Firebase\JWT\ExpiredException $e) {
        // Return an error response if token is expired
        $responseBody = json_encode(['error' => 'Token expired']);
        return $response->withStatus(401)->withHeader('Content-Type', 'application/json')->getBody()->write($responseBody);
    } catch (\Firebase\JWT\SignatureInvalidException $e) {
        // Return an error response if token signature is invalid
        $responseBody = json_encode(['error' => 'Invalid token']);
        return $response->withStatus(401)->withHeader('Content-Type', 'application/json')->getBody()->write($responseBody);
    } catch (\Exception $e) {
        // Return a generic error response for other exceptions
        $responseBody = json_encode(['error' => 'An error occurred']);
        return $response->withStatus(500)->withHeader('Content-Type', 'application/json')->getBody()->write($responseBody);
    }
}





// Method to update user information based on username
public function updateUserById(Request $request, Response $response, $args)
{
    // Retrieve JWT token from headers
    $token = $request->getAttribute('token');

    // Check if token is present and valid
    if (!$token || !isset($token['id'])) {
        $response->getBody()->write(json_encode([
            'error' => 'Unauthorized access'
        ]));
        return $response->withStatus(401)->withHeader('Content-Type', 'application/json');
    }

    // Get the user ID from the JWT token
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