<script lang="ts">
    import { loginUser } from '../../api/users';
    import { goto } from '$app/navigation';

    let email = '';
    let password = '';
    let errorMessage = '';
    let tokenData = null;

    async function handleLogin() {
    try {
        const response = await loginUser(email, password);
        console.log('Login successful', response);

        // Extract username from JWT token
        const token = response.token;
        const decodedToken = parseJwt(token);
        tokenData = decodedToken; // Save token data for inspection
        const username = decodedToken.username; // Use username directly

        // Redirect to user's profile page based on username
        goto(`/users/${encodeURIComponent(username)}`);
    } catch (error) {
        errorMessage = error.message; // Use error.message to access the error message
    }
}

    // Function to parse JWT token
    function parseJwt(token: string) {
        const base64Url = token.split('.')[1];
        const base64 = base64Url.replace(/-/g, '+').replace(/_/g, '/');
        const jsonPayload = decodeURIComponent(atob(base64).split('').map(function(c) {
            return '%' + ('00' + c.charCodeAt(0).toString(16)).slice(-2);
        }).join(''));

        return JSON.parse(jsonPayload);
    }
</script>

<div class="min-h-screen flex items-center justify-center bg-gray-200 text-blue-700">
    <div class="bg-gray-100 shadow-md rounded px-20 pt-10 pb-10 mb-6 w-256">
        <h2 class="text-4xl mb-8 font-bold">
            Login
        </h2>
        <div class="mb-8">
            <label class="block text-gray-700 text-lg font-bold mb-4" for="email">
                Email
            </label>
            <input class="shadow appearance-none border rounded w-full py-4 px-5 text-lg text-gray-700 leading-tight focus:outline-none focus:shadow-outline" id="email" type="email" placeholder="Email" bind:value={email}>
        </div>
        <div class="mb-8">
            <label class="block text-gray-700 text-lg font-bold mb-4" for="password">
                Password
            </label>
            <input class="shadow appearance-none border rounded w-full py-4 px-5 text-lg text-gray-700 mb-5 leading-tight focus:outline-none focus:shadow-outline" id="password" type="password" placeholder="*********" bind:value={password}>
        </div>
        {#if errorMessage}
            <p class="text-red-500 text-base italic">{errorMessage}</p>
        {/if}
        {#if tokenData}
            <pre>{JSON.stringify(tokenData, null, 2)}</pre>
        {/if}
        <div class="flex items-center justify-between mt-4">
            <button on:click={handleLogin} class="bg-blue-500 hover:bg-blue-700 text-white font-bold py-4 px-10 rounded focus:outline-none focus:shadow-outline mr-4">
                Sign In
            </button>
            <a href="/register" class="bg-green-500 hover:bg-green-700 text-white font-bold py-4 px-10 rounded focus:outline-none focus:shadow-outline">Register</a>
        </div>
    </div>
</div>
