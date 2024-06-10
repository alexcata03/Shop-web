export const loginUser = async (email: string, password: string) => {
    if (!email || !password) {
        throw new Error("Email and password must be provided");
    }

    try {
        console.log("Attempting to login with email:", email);
        const response = await fetch("http://localhost:8000/login", {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({ email, password }),
            credentials: 'include',
        });

        console.log("Login response status:", response.status);

        if (!response.ok) {
            const errorData = await response.json();
            console.error("Error response data:", errorData);
            throw new Error(errorData.error || "Failed to login");
        }

        const responseData = await response.json();
        console.log("Login successful, response data:", responseData);
        return responseData;
    } catch (error) {
        console.error("Failed to login:", error);
        throw error;
    }
};
export const registerUser = async (email: string, password: string, firstName: string, lastName: string, username: string) => {
    // Check for missing parameters
    if (!email || !password || !firstName || !lastName || !username) {
        throw new Error("Email, password, first name, last name, and username must be provided");
    }

    try {
        console.log("Attempting to register with email:", email);
        const response = await fetch("http://localhost:8000/register", {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({ email, password, firstName, lastName, username }),
            credentials: 'include',
        });

        console.log("Registration response status:", response.status);

        if (!response.ok) {
            const errorData = await response.json();
            console.error("Error response data:", errorData);
            throw new Error(errorData.error || "Failed to register");
        }

        const responseData = await response.json();
        console.log("Registration successful, response data:", responseData);
        return responseData;
    } catch (error) {
        console.error("Failed to register:", error);
        throw error;
    }
};
export const getUserByUsername = async (username: string) => {
    if (!username) {
        throw new Error("Username is required");
    }

    try {
        const response = await fetch(`http://localhost:8000/users/${username}`, {
            headers: {
                'Accept': 'application/json',
            }
        });

        if (!response.ok) {
            const errorData = await response.json();
            throw new Error(errorData.error || "Failed to fetch user data");
        }

        const userData = await response.json();
        return userData;
    } catch (error) {
        console.error("Failed to fetch user data:", error);
        throw error;
    }
};
