# Project Overview

The sand-backend project is a RESTful API backend designed to handle user authentication, data management, and communication with the frontend. It provides essential services to support the functionality of the sand application.

# System Architecture

The architecture is based on a microservices approach, ensuring scalability and maintainability. The main components include:

- **API Gateway:** Acts as a single entry point for all the client requests.
- **Authentication Service:** Manages user authentication and authorization.
- **Data Service:** Handles data storage and retrieval operations.

# Directory Structure

The directory structure of the project is organized as follows:

```
/sand-backend
    /src
        /api
        /config
        /controllers
        /models
        /routes
    /tests
    README.md
```

# Database Schema

The project utilizes a relational database. Below are some key entities:

- **User:** Stores user information, including username and password hash.
- **Post:** Represents a data entry made by users.

# API Endpoints

Below are some essential API endpoints:

- **POST /api/auth/register** - Register a new user.
- **POST /api/auth/login** - Login an existing user.
- **GET /api/posts** - Fetch all posts.

# Setup Instructions

1. Clone the repository:
   ```bash
   git clone https://github.com/Shiv14Shivam/sand-backend.git
   ```
2. Navigate to the project directory:
   ```bash
   cd sand-backend
   ```
3. Install dependencies:
   ```bash
   npm install
   ```
4. Configure environment variables:
   Create a `.env` file and set the required variables.
5. Start the application:
   ```bash
   npm start
   ```
