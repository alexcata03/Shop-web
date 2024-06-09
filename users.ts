// src/api/users.ts

export const loginUser = async (email: string, password: string) => {
    try {
        const response = await fetch("http://localhost:8000/login", {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({ email, password }),
            credentials: 'include', // Include credentials if your backend requires it
        });

        if (!response.ok) {
            const errorData = await response.json();
            throw new Error(errorData.error || "Failed to login");
        }

        return response.json(); // Return the JSON response
    } catch (error) {
        console.error("Failed to login: " + error);
        throw error; // Throw the error for handling in the calling code
    }
};
