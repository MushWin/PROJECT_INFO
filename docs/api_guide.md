# API Documentation

## Authentication

There are two ways to authenticate with the API:

### 1. Using API Key

Send the API key in the header of your request:

```
X-API-KEY: c8a18ab237b044d9edaefde6ff3cae28
```

### 2. Using JWT Token

First, obtain a JWT token by logging in:

```
POST /api/login
{
    "username": "admin",
    "password": "password"
}
```

Then use the token in subsequent requests:

```
Authorization: Bearer your_jwt_token_here
```

## Testing with Postman

### Testing Login

1. Open Postman and create a new request
2. Set the request to `POST`
3. Enter the URL: `http://localhost/api/login`
4. Click on the "Body" tab
5. Select "raw" and choose "JSON" from the dropdown
6. Enter the login credentials:
   ```json
   {
       "username": "admin",
       "password": "password"
   }
   ```
7. Click "Send"
8. You should receive a response with a JWT token:
   ```json
   {
       "success": true,
       "token": "jwt_token_here",
       "expires": 1635529200,
       "user": {
           "id": 1,
           "username": "admin",
           "role": "admin"
       }
   }
   ```

### Using the JWT Token

1. Copy the token from the response
2. Create a new request for any protected endpoint
3. Go to the "Headers" tab
4. Add a new header:
   - Key: `Authorization`
   - Value: `Bearer your_jwt_token_here` (replace with your actual token)
5. Send your request

### Using API Key

Alternatively, to authenticate with the API key:

1. Go to the "Headers" tab
2. Add a new header:
   - Key: `X-API-KEY`
   - Value: `c8a18ab237b044d9edaefde6ff3cae28`
3. Send your request

### Verifying Authentication Works

To confirm your authentication is working properly:

#### 1. Test Successful Login

1. Send the login request as described above
2. Verify you receive a 200 OK status code
3. Check that the response contains a valid JWT token, expiration time, and user details
4. The success field should be `true`

#### 2. Test Failed Login

1. Create another POST request to `/api/login`
2. Enter incorrect credentials:
   ```json
   {
       "username": "admin",
       "password": "wrong_password"
   }
   ```
3. Send the request
4. You should receive a 401 Unauthorized status code
5. The response should contain an error message

#### 3. Test Protected Endpoint with Valid Token

1. After a successful login, copy the JWT token
2. Create a new GET request to a protected endpoint (e.g., `/api/user` or `/api/data`)
3. Add the Authorization header with your token
4. Send the request
5. You should receive a 200 OK response with the expected data

#### 4. Test Protected Endpoint with Invalid Token

1. Create a GET request to the same protected endpoint
2. Add an Authorization header with an invalid token:
   ```
   Authorization: Bearer invalid_token_here
   ```
3. Send the request
4. You should receive a 401 Unauthorized status code

#### 5. Test API Key Authentication

1. Create a GET request to a protected endpoint
2. Instead of Authorization header, add the X-API-KEY header:
   ```
   X-API-KEY: c8a18ab237b044d9edaefde6ff3cae28
   ```
3. Send the request
4. You should receive a 200 OK response with the expected data

#### Troubleshooting

If authentication isn't working:

- Check if your server is properly configured to handle JWT tokens
- Verify the API key in your request matches the one in `config.php`
- Ensure your token hasn't expired (check the expiration time in the login response)
- Look for any CORS issues if testing from a different domain
- Check server logs for additional error information

##### API Returns HTML Instead of JSON

If your API request returns HTML content (like your website's index page) instead of JSON:

1. **Check the URL format**: Ensure you're using the exact endpoint path
   - Correct: `http://localhost/api/login`
   - Not: `http://localhost/index.php/api/login` or `http://localhost/api/login/`

2. **Verify request headers**:
   - Set `Content-Type: application/json` header
   - For POST requests, make sure the body is raw JSON (not form-data or x-www-form-urlencoded)

3. **Check your server configuration**:
   - Make sure your `.htaccess` is properly set up to route API requests
   - If using Apache, confirm `mod_rewrite` is enabled
   - Verify PHP is parsing API requests correctly

4. **Quick API test**:
   Create a test file to verify API routing:

```
// Test with: http://localhost/api_test.php
<?php
header('Content-Type: application/json');
echo json_encode(['status' => 'API routing working']);
?>
```

5. **Check for 404 handling**: Some server configurations redirect all 404s to index.php, which might be returning the home page HTML

##### API Returns 200 OK but No JSON Response

If your API request returns a 200 OK status but doesn't include the expected JSON response:

1. **Check response headers**: Make sure the API is setting `Content-Type: application/json` header.
   - In Postman, look at the "Headers" tab in the response section

2. **PHP Output Issues**:
   - Check for any PHP warnings or notices that might interfere with JSON output
   - Look for whitespace or echo statements before the JSON response
   - Ensure output buffering is handled properly

3. **Missing Headers in Server Code**:
   - The API endpoint should explicitly set `header('Content-Type: application/json');`
   - Use `exit()` or `die()` after sending JSON to prevent additional output

4. **Test with Simple Endpoint**:
   - Try the `/api/auth_test.php` endpoint to verify basic authentication functionality
   - This will help isolate whether the issue is with your routing or the login implementation

5. **Error Reporting**: 
   - Temporarily enable PHP error display in your API script to catch silent errors:
   ```php
   ini_set('display_errors', 1);
   error_reporting(E_ALL);
   ```

## Endpoints

### Authentication

#### Login

```
POST /api/login

Request:
{
    "username": "admin",
    "password": "password"
}

Response:
{
    "success": true,
    "token": "jwt_token_here",
    "expires": 1635529200,
    "user": {
        "id": 1,
        "username": "admin",
        "role": "admin"
    }
}
```

### User

#### Get Current User

```
GET /api/user

Response:
{
    "id": 1,
    "username": "admin",
    "role": "admin"
}
```

### Data

#### Get All Data

```
GET /api/data

Response:
{
    "success": true,
    "data": [
        {
            "id": 1,
            "title": "First Item",
            "description": "Description of first item"
        },
        ...
    ]
}
```

#### Get Data by ID

```
GET /api/data/{id}

Response:
{
    "success": true,
    "data": {
        "id": 1,
        "title": "First Item",
        "description": "Description of first item"
    }
}
```

#### Create Data

```
POST /api/data

Request:
{
    "title": "New Item",
    "description": "Description of new item"
}

Response:
{
    "success": true,
    "data": {
        "id": 4,
        "title": "New Item",
        "description": "Description of new item"
    }
}
```

#### Update Data

```
PUT /api/data/{id}

Request:
{
    "title": "Updated Item",
    "description": "Updated description"
}

Response:
{
    "success": true,
    "data": {
        "id": 1,
        "title": "Updated Item",
        "description": "Updated description"
    }
}
```

#### Delete Data

```
DELETE /api/data/{id}

Response:
HTTP 204 No Content
```

## Error Handling

All errors will return an appropriate HTTP status code and a JSON object with an error message:

```
{
    "error": "Error message here"
}
```

Common status codes:
- 400: Bad Request
- 401: Unauthorized
- 404: Not Found
- 500: Internal Server Error
