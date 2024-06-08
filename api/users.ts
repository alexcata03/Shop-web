const API_BASE_URL = 'http://localhost:8000';

export async function loginUser(email: string, password: string) {
    try {
        const response = await fetch(`${API_BASE_URL}/login`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'Accept': 'application/json',
            },
            body: JSON.stringify({ email, password })
        });

        if (!response.ok) {
            throw new Error("Failed to login");
        }

        return await response.json();
    } catch (error) {
        console.error("Failed to login: " + error);
        throw error;
    }
}

export async function registerUser(firstName: string, lastName: string, email: string, password: string) {
    try {
        const response = await fetch(`${API_BASE_URL}/register`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'Accept': 'application/json',
            },
            body: JSON.stringify({ firstName, lastName, email, password })
        });

        if (!response.ok) {
            throw new Error("Failed to register");
        }

        return await response.json();
    } catch (error) {
        console.error("Failed to register: " + error);
        throw error;
    }
}
