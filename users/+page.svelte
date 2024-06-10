<script>
    let username = '';
    let email = '';
    let phone = '';
    let address = '';
    let firstName = '';
    let lastName = '';

    // Function to handle image selection
    function chooseImage() {
        // Add logic to choose image
    }

    // Function to handle saving user data
    function saveUserData() {
        // Add logic to save user data
    }

    // Function to handle logout
    function logout() {
        // Add logic to logout
    }
</script>

<div class="min-h-screen">
    <!-- Top bar with user information and logout -->
<div class="flex items-center justify-between bg-gray-200 p-4">
    <!-- User information -->
    <div class="flex items-center">
        <!-- User icon or image -->
        <div class="w-12 h-12 rounded-full bg-gray-300 flex items-center justify-center">
            <!-- User icon -->
            <svg class="w-8 h-8 text-gray-500" fill="none" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" viewBox="0 0 24 24" stroke="currentColor">
                <path d="M12 14l9-5-9-5-9 5 9 5z"></path>
                <path d="M12 14l9-5-9-5-9 5 9 5z"></path>
                <path d="M12 14l9-5-9-5-9 5 9 5z"></path>
            </svg>
        </div>
        <!-- First and last name -->
        <div class="ml-4">
            <p class="font-bold">John Doe</p> <!-- Replace with dynamic binding -->
        </div>
    </div>
    <!-- Buttons for Shopping Cart, Orders, and Products -->
    <div class="flex items-center space-x-4">
        <button class="text-gray-500 hover:text-gray-700">Shopping Cart</button>
        <button class="text-gray-500 hover:text-gray-700">Orders</button>
        <button class="text-gray-500 hover:text-gray-700">Products</button>
    </div>
    <!-- Logout button -->
    <button class="text-gray-500 hover:text-gray-700">
        <!-- Logout icon -->
        <svg class="w-6 h-6" fill="none" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" viewBox="0 0 24 24" stroke="currentColor">
            <path d="M12 8l-3 3m0 0l3 3m-3-3h14"></path>
        </svg>
    </button>
</div>
    <!-- Main content -->
    <div class="flex items-center justify-center mt-8">
        <!-- Circle image with "Choose Image" button -->
        <div class="relative">
            <!-- Circle image -->
            <div class="w-32 h-32 bg-gray-300 rounded-full"></div>
            <!-- Choose image button -->
            <button class="absolute bottom-0 right-0 bg-blue-500 text-white rounded-full px-3 py-1 text-sm shadow hover:bg-blue-600" on:click={chooseImage}>
                Choose Image
            </button>
        </div>
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
</div>

