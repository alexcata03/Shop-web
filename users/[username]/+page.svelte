<script lang="ts">
    import { onMount } from 'svelte';
    import { getUserByUsername, updateUserById } from '../../../api/users';
    import { logoutUser } from '../../../api/users';

    let username = '';
    let email = '';
    let phone = '';
    let address = '';
    let firstName = '';
    let lastName = '';
    let error = '';
    let loading = true;
    let tokenData = null;

    // Function to parse JWT token
    function parseJwt(token: string) {
        const base64Url = token.split('.')[1];
        const base64 = base64Url.replace(/-/g, '+').replace(/_/g, '/');
        return JSON.parse(decodeURIComponent(atob(base64)));
    }

    onMount(() => {
        // Retrieve JWT token from localStorage
        const jwtToken = localStorage.getItem('jwtToken');
        
        if (jwtToken) {
            const decodedToken = parseJwt(jwtToken);
            tokenData = decodedToken;
            username = decodedToken.username;
            console.log('Username extracted from token:', username);
            fetchData();
        } else {
            console.error("Token not available. User not logged in.");
            // Possible actions: Redirect to login page, show error message, etc.
        }
    });

    async function logout() {
        try {
            await logoutUser();
            localStorage.removeItem('jwtToken'); // Remove JWT token from localStorage
            console.log('User logged out');
            window.location.href = '/login';
        } catch (error) {
            console.error("Failed to logout:", error);
        }
    }

    async function saveUserData() {
        try {
            if (!username || !email || !phone || !address || !firstName || !lastName) {
                throw new Error("All fields are required");
            }

            const userData = {
                username,
                email,
                phone,
                address,
                firstName,
                lastName
            };

            console.log('Saving user data:', userData);
            await updateUserById(tokenData.userId, userData);
            console.log('User data saved successfully');
        } catch (error) {
            console.error("Failed to save user data:", error);
        }
    }

    async function fetchData() {
        try {
            loading = true;
            error = '';

            if (!username) {
                throw new Error("Username is required");
            }

            console.log('Fetching user data for username:', username);
            const userData = await getUserByUsername(username);
            console.log('User data fetched:', userData);

            email = userData.email || '';
            phone = userData.phone || '';
            address = userData.address || '';
            firstName = userData.firstName || '';
            lastName = userData.lastName || '';

            console.log('User data updated:', { email, phone, address, firstName, lastName });
        } catch (err) {
            console.error("Failed to fetch user data:", err);
            error = err.message || 'Failed to fetch user data';
        } finally {
            loading = false;
        }
    }
</script>

<div class="min-h-screen">
    <!-- Top bar with user information and logout -->
    <!-- Replace static content with dynamic bindings -->
    <div class="flex items-center justify-between bg-gray-200 p-4">
        <!-- User information -->
        <div class="flex items-center">
            <!-- User icon or image -->
            <div class="w-12 h-12 rounded-full bg-gray-300 flex items-center justify-center">
                <!-- User icon -->
                <svg class="w-8 h-8 text-gray-500" fill="none" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" viewBox="0 0 24 24" stroke="currentColor">
                    <path d="M12 14l-3 3m0 0l3 3m-3-3h14"></path>
                </svg>
            </div>
            <!-- First and last name -->
            <div class="ml-4">
                <p class="font-bold">{firstName} {lastName}</p> <!-- Bind first and last name dynamically -->
            </div>
        </div>
        <!-- Buttons for Shopping Cart, Orders, and Products -->
        <div class="flex items-center space-x-4">
            <button class="text-gray-500 hover:text-gray-700">Shopping Cart</button>
            <button class="text-gray-500 hover:text-gray-700">Orders</button>
            <button class="text-gray-500 hover:text-gray-700">Products</button>
        </div>
        <!-- Logout button -->
        <button class="text-gray-500 hover:text-gray-700" on:click={logout}>
            <!-- Logout icon -->
            <svg class="w-6 h-6" fill="none" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" viewBox="0 0 24 24" stroke="currentColor">
                <path d="M12 8l-3 3m0 0l3 3m-3-3h14"></path>
            </svg>
        </button>
    </div>
    <!-- Main content -->
    <div class="flex items-center justify-center mt-8">
        <!-- User information form -->
        <form class="ml-8" on:submit|preventDefault="{saveUserData}">
            <div class="mb-4">
                <label class="block text-gray-700 text-sm font-bold mb-2" for="username">Username</label>
                <input class="shadow appearance-none border rounded w-64 py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline" id="username" type="text" placeholder="Username" bind:value={username}>
            </div>
            <div class="mb-4">
                <label class="block text-gray-700 text-sm font-bold mb-2" for="email">Email</label>
                <input class="shadow appearance-none border rounded w-64 py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline" id="email" type="email" placeholder="Email" bind:value={email}>
            </div>
            <div class="mb-4">
                <label class="block text-gray-700 text-sm font-bold mb-2" for="phone">Phone</label>
                <input class="shadow appearance-none border rounded w-64 py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline" id="phone" type="text" placeholder="Phone" bind:value={phone}>
            </div>
            <div class="mb-4">
                <label class="block text-gray-700 text-sm font-bold mb-2" for="address">Address</label>
                <input class="shadow appearance-none border rounded w-64 py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline" id="address" type="text" placeholder="Address" bind:value={address}>
            </div>
            <div class="mb-4">
                <label class="block text-gray-700 text-sm font-bold mb-2" for="firstName">First Name</label>
                <input class="shadow appearance-none border rounded w-64 py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline" id="firstName" type="text" placeholder="First Name" bind:value={firstName}>
            </div>
            <div class="mb-4">
                <label class="block text-gray-700 text-sm font-bold mb-2" for="lastName">Last Name</label>
                <input class="shadow appearance-none border rounded w-64 py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline" id="lastName" type="text" placeholder="Last Name" bind:value={lastName}>
            </div>
            <!-- Save button -->
            <button type="submit" class="bg-blue-500 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded focus:outline-none focus:shadow-outline">Save</button>
        </form>
    </div>

    <!-- Error and loading states -->
    {#if error}
        <p class="text-red-500">{error}</p>
    {/if}
    {#if loading}
        <p>Loading...</p>
    {/if}
</div>

