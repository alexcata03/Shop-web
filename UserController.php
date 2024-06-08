<?php
declare(strict_types=1);

namespace App\Controllers;

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use PDO;
use Lcobucci\JWT\Builder;
use Lcobucci\JWT\Signer\Hmac\Sha256;
use Lcobucci\JWT\Parser;
use Lcobucci\JWT\Signer\Key;
use DateTimeImmutable;

class UserController
{
    private PDO $db;
    private string $jwtSecret;

    public function __construct(PDO $db)
    {
        $this->db = $db;
        $this->jwtSecret = bin2hex(random_bytes(32)); // Generate random JWT secret key
    }
    
    // Method to generate JWT token
    private function generateToken($userId)
    {
        // Create signer
        $signer = new Sha256();
    
        // Generate key from secret
        $key = new Key($this->jwtSecret);
    
        // Create token builder
        $token = (new Builder())
            ->issuedBy('http://localhost:5173/') // Configures the issuer (your Vite server URL)
            ->permittedFor('http://localhost:8000') // Configures the audience (your PHP server URL)
            ->issuedAt(new DateTimeImmutable()) // Configures the time that the token was issued
            ->expiresAt((new DateTimeImmutable())->modify('+14 hour'))
            ->withClaim('userId', $userId) // Configures a new claim, called "userId"
            ->getToken($signer, $key); // Retrieves the generated token
    
        return $token;
    }

    //Method to get user id from token
    private function getUserIdFromToken(string $token): ?int
    {
        try {
            // Decode the JWT token payload
            $tokenParts = explode('.', $token);
            $payload = base64_decode($tokenParts[1]);
            $decodedPayload = json_decode($payload, true);

            // Retrieve the user ID from the decoded payload
            $userId = $decodedPayload['userId'] ?? null;

            return $userId;
        } catch (\Throwable $e) {
            // Log or handle the error if decoding fails
            error_log('Error decoding JWT token payload: ' . $e->getMessage());
            return null;
        }
    }


    // Login
    public function login(Request $request, Response $response, $args): Response
    {
        // Retrieve username and password from the request
        $loginData = $request->getParsedBody();
        $username = $loginData['username'] ?? null;
        $password = $loginData['password'] ?? null;

        // Check if both username and password are provided
        if (!$username || !$password) {
            error_log('Error: Username and password are required');
            $response->getBody()->write(json_encode([
                'error' => 'Username and password are required',
                'username' => $username,
                'password' => $password
            ]));
            return $response->withHeader('Content-Type', 'application/json')->withStatus(400);
        }

        // Perform database query to fetch user data based on username
        $stmt = $this->db->prepare('SELECT * FROM users WHERE username = ? LIMIT 1');
        $stmt->execute([$username]);
        $user = $stmt->fetch();

        // Check if user exists
        if (!$user) {
            error_log('Error: Invalid username');
            $response->getBody()->write(json_encode([
                'error' => 'Invalid username',
                'username' => $username,
                'password' => $password
            ]));
            return $response->withHeader('Content-Type', 'application/json')->withStatus(401);
        }

        // Get the hashed password from the database
        $passwordFromDb = $user['password'];
        // Manually verify the password
        if ($password !== $passwordFromDb) {
            // Passwords don't match, render error message
            error_log('Error: Invalid password');
            $response->getBody()->write(json_encode([
                'error' => 'Invalid password',
                'username' => $username,
                'password' => $password
            ]));
            return $response->withHeader('Content-Type', 'application/json')->withStatus(401);
        }

        // Passwords match, proceed with login
        // Generate JWT token
        $token = $this->generateToken($user['id']);

        // Return success message along with the JWT token
        $response->getBody()->write(json_encode([
            'message' => 'Login successful',
            'userId' => $user['id'],
            'username' => $username,
            'token' => (string) $token // Convert token object to string
        ]));
        return $response->withHeader('Content-Type', 'application/json')->withStatus(200);
    }
    // Method for user registration
    public function createUser(Request $request, Response $response, $args): Response
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
            $response->getBody()->write(json_encode(['error' => 'Username already exists in the database']));
            return $response->withHeader('Content-Type', 'application/json')->withStatus(400);
        }

        if ($resultEmail['count'] > 0) {
            // Email already exists, return an error
            $response->getBody()->write(json_encode(['error' => 'Email already exists in the database']));
            return $response->withHeader('Content-Type', 'application/json')->withStatus(400);
        }

        // If no errors, proceed with creating the user
        // Prepare and execute the SQL query to insert the new user
        $stmt = $this->db->prepare('INSERT INTO users (username, email, password, phone, address, userStatus, firstName, lastName) VALUES (?, ?, ?, ?, ?, 1, ?, ?)');
        $stmt->execute([$username, $email, $password, $phone, $address, $firstName, $lastName]);

       // Get the user ID of the newly created user
       $userId = $this->db->lastInsertId();
       // Generate JWT token
       $token = $this->generateToken($userId);

       // Return success message along with the JWT token
       $response->getBody()->write(json_encode([
           'message' => 'User registered successfully',
           'username' => $username,
           'email' => $email,
           'phone' => $phone,
           'address' => $address,
           'userStatus' => 1, // Set userStatus to 1 by default
           'firstName' => $firstName,
           'lastName' => $lastName,
           'userId' => $userId, // Pass the user ID to the response
           'token' => (string) $token // Convert token object to string
       ]));
       return $response->withHeader('Content-Type', 'application/json')->withStatus(200);
    }

    // Logout
    public function logout(Request $request, Response $response, $args): Response
    {
        // Return logout success message
        $response->getBody()->write(json_encode(['message' => 'Logged out successfully']));
        return $response->withHeader('Content-Type', 'application/json')->withStatus(200);
    }

    // Method to get all users
    public function getAll(Request $request, Response $response, $args): Response
    {
        // Extract token from Authorization header
        $token = $request->getHeaderLine('Authorization');

        // Validate and extract user ID from token
        $userId = $this->getUserIdFromToken($token);

        if ($userId === null) {
            // Token is invalid or user ID is not found, return error response
            $response->getBody()->write(json_encode(['message' => 'Invalid token']));
            return $response->withHeader('Content-Type', 'application/json')->withStatus(401);
        }

        // Proceed with fetching users based on user ID
        // Get the user status from the database
        $stmt = $this->db->prepare('SELECT userStatus FROM users WHERE id = ?');
        $stmt->execute([$userId]);
        $userStatus = $stmt->fetchColumn();

        // Check if the user has permission to view all users
        if ($userStatus != 2) {
            $response->getBody()->write(json_encode(['message' => 'You do not have permission to view all users']));
            return $response->withHeader('Content-Type', 'application/json')->withStatus(403);
        }

        // Fetch all users from the database
        $stmt = $this->db->query('SELECT * FROM users');
        $users = $stmt->fetchAll();

        // Encode the data to JSON
        $responseData = json_encode(['users' => $users]);

        // Set the response body and headers
        $response->getBody()->write($responseData);
        $response = $response->withHeader('Content-Type', 'application/json')->withStatus(200);

        return $response;
    }


    public function getUserByUsername(Request $request, Response $response, $args): Response
    {
        // Extract token from Authorization header
        $token = $request->getHeaderLine('Authorization');

        // Validate and extract user ID from token
        $userId = $this->getUserIdFromToken($token);

        if ($userId === null) {
            // Token is invalid or user ID is not found, return error response
            $response->getBody()->write(json_encode(['message' => 'Invalid token']));
            return $response->withHeader('Content-Type', 'application/json')->withStatus(401);
        }

        // Get the user status from the database
        $stmt = $this->db->prepare('SELECT userStatus FROM users WHERE id =?');
        $stmt->execute([$userId]);
        $userStatus = $stmt->fetchColumn();

        // Check if the user has permission to view other users
        if ($userStatus != 2) {
            $response->getBody()->write(json_encode(['message' => 'You do not have permission to view other users']));
            return $response->withHeader('Content-Type', 'application/json')->withStatus(403);
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
            $response->getBody()->write(json_encode(['message' => 'User not found']));
            return $response->withHeader('Content-Type', 'application/json')->withStatus(404);
        }

        // User found, return the user information
        $response->getBody()->write(json_encode(['user' => $user]));
        return $response->withHeader('Content-Type', 'application/json')->withStatus(200);
    }

    // Method to update user information based on username
   public function updateUserById(Request $request, Response $response, $args): Response
{
    // Extract token from Authorization header
    $token = $request->getHeaderLine('Authorization');

    // Validate and extract user ID from token
    $userId = $this->getUserIdFromToken($token);

    if ($userId === null) {
        // Token is invalid or user ID is not found, return error response
        $response->getBody()->write(json_encode(['message' => 'Invalid token']));
        return $response->withHeader('Content-Type', 'application/json')->withStatus(401);
    }

    // Get the user status from the database
    $stmt = $this->db->prepare('SELECT userStatus FROM users WHERE id =?');
    $stmt->execute([$userId]);
    $userStatus = $stmt->fetchColumn();

    // Check if the user has permission to update other users
    if ($userStatus != 2) {
        $response->getBody()->write(json_encode(['message' => 'You do not have permission to update other users']));
        return $response->withHeader('Content-Type', 'application/json')->withStatus(403);
    }

    // Get the user ID from the URL parameters
    $id = $args['id'];

    // Retrieve updated user data from the request body
    $requestBody = $request->getBody()->getContents();
    error_log('Request Body: ' . $requestBody);

    // Try to parse the request body manually
    parse_str($requestBody, $parsedBody);
    error_log('Parsed Body: ' . print_r($parsedBody, true));

    // Check if $parsedBody is empty or not an array
    if (empty($parsedBody) || !is_array($parsedBody)) {
        error_log('Received invalid data for update: ' . print_r($parsedBody, true));
        $response->getBody()->write(json_encode(['message' => 'Invalid data sent for update']));
        return $response->withHeader('Content-Type', 'application/json')->withStatus(400);
    }

    // Use the parsed body as user data
    $userData = $parsedBody;

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
        $response->getBody()->write(json_encode(['message' => 'No fields provided for update']));
        return $response->withHeader('Content-Type', 'application/json')->withStatus(400);
    }

    // Prepare the SQL query with dynamic placeholders
    $sql = 'UPDATE users SET ' . implode(', ', $placeholders) . ' WHERE id = ?';
    $values[] = $id;

    // Execute the SQL query
    $stmt = $this->db->prepare($sql);
    $stmt->execute($values);

    // Return a success message
    $response->getBody()->write(json_encode(['message' => 'User updated successfully']));
    return $response->withHeader('Content-Type', 'application/json')->withStatus(200);
}

    // Method to delete user based on username
    public function deleteUserByUsername(Request $request, Response $response, $args): Response
    {
        // Get the user ID from the session
        $userId = $_SESSION['userId'];

        // Get the user status from the database
        $stmt = $this->db->prepare('SELECT userStatus FROM users WHERE id =?');
        $stmt->execute([$userId]);
        $userStatus = $stmt->fetchColumn();

        // Check if the user has permission to delete other users
        if ($userStatus != 2) {
            $response->getBody()->write(json_encode(['message' => 'You do not have permission to delete other users']));
            return $response->withHeader('Content-Type', 'application/json')->withStatus(403);
        }

        // Get the username from the URL parameters
        $username = $args['username'];

        // Prepare and execute the SQL query to delete the user
        $stmt = $this->db->prepare('DELETE FROM users WHERE username = ?');
        $stmt->execute([$username]);

        // Return a success message
        $response->getBody()->write(json_encode(['message' => 'User deleted successfully']));
        return $response->withHeader('Content-Type', 'application/json')->withStatus(200);
    }
}
?>
