export const getAllProducts = async() => {
    try {
        const response = await fetch("http://localhost:8000/products", {
            headers: {
                'Accept': 'text/html',
            },
        });

        if (!response.ok) {
            throw new Error("Failed to fetch products");
        }

        if (response === undefined || response === null) {
            throw new Error("No products were fetched from the server");
        }

        return response.json();
    } catch (error) {
        console.error("Failed to fetch all products from the server: " + error);
    }
}
