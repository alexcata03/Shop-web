<script lang="ts">
    import { registerUser } from '../../api/users';
    import { onMount } from 'svelte';

    let email = '';
    let password = '';
    let firstName = '';
    let lastName = '';
    let username = '';
    let errorMessage = '';

    async function handleRegister(event) {
        event.preventDefault(); // Prevent default form submission behavior
        try {
            const response = await registerUser(email, password, firstName, lastName, username);
            console.log('Registration successful', response);

            // Handle successful registration (e.g., redirect to login page)
        } catch (error) {
            errorMessage = error.message; // Use error.message to access the error message
        }
    }

    // This function will run when the component is mounted
    onMount(() => {
        // Add event listener to the form to call handleRegister function on form submission
        const form = document.getElementById('registerForm');
        form.addEventListener('submit', handleRegister);
    });
</script>

<div class="min-h-screen flex items-center justify-center bg-gray-200 text-blue-700"> <!-- Darker gray background for the surrounding area -->
    <div class="bg-gray-100 shadow-md rounded px-20 pt-10 pb-10 mb-6 w-256"> <!-- Adjusted width and padding, and darker gray background for the box -->
        <h2 class="text-4xl mb-8 font-bold"> <!-- Increased font size and made bold -->
            Register
        </h2> <!-- Increased font size -->
        <form id="registerForm">
            <div class="mb-8"> <!-- Increased margin bottom -->
                <label class="block text-gray-700 text-lg font-bold mb-4" for="username"> <!-- Increased font size -->
                    Username
                </label>
                <input class="shadow appearance-none border rounded w-full py-4 px-5 text-lg text-gray-700 leading-tight focus:outline-none focus:shadow-outline" id="username" type="text" placeholder="Username" bind:value={username}>
            </div>
            <div class="mb-8"> <!-- Increased margin bottom -->
                <label class="block text-gray-700 text-lg font-bold mb-4" for="email"> <!-- Increased font size -->
                    Email
                </label>
                <input class="shadow appearance-none border rounded w-full py-4 px-5 text-lg text-gray-700 leading-tight focus:outline-none focus:shadow-outline" id="email" type="email" placeholder="Email" bind:value={email}>
            </div>
            <div class="mb-8"> <!-- Increased margin bottom -->
                <label class="block text-gray-700 text-lg font-bold mb-4" for="password"> <!-- Increased font size -->
                    Password
                </label>
                <input class="shadow appearance-none border rounded w-full py-4 px-5 text-lg text-gray-700 mb-5 leading-tight focus:outline-none focus:shadow-outline" id="password" type="password" placeholder="*********" bind:value={password}>
            </div>
            <div class="mb-8"> <!-- Increased margin bottom -->
                <label class="block text-gray-700 text-lg font-bold mb-4" for="firstName"> <!-- Increased font size -->
                    First Name
                </label>
                <input class="shadow appearance-none border rounded w-full py-4 px-5 text-lg text-gray-700 leading-tight focus:outline-none focus:shadow-outline" id="firstName" type="text" placeholder="First Name" bind:value={firstName}>
            </div>
            <div class="mb-8"> <!-- Increased margin bottom -->
                <label class="block text-gray-700 text-lg font-bold mb-4" for="lastName"> <!-- Increased font size -->
                    Last Name
                </label>
                <input class="shadow appearance-none border rounded w-full py-4 px-5 text-lg text-gray-700 leading-tight focus:outline-none focus:shadow-outline" id="lastName" type="text" placeholder="Last Name" bind:value={lastName}>
            </div>
            {#if errorMessage}
                <p class="text-red-500 text-base italic">{errorMessage}</p> <!-- Adjusted font size -->
            {/if}
            <div class="flex items-center justify-between mt-4"> <!-- Added margin top -->
                <button type="submit" class="bg-blue-500 hover:bg-blue-700 text-white font-bold py-4 px-10 rounded focus:outline-none focus:shadow-outline mr-4"> <!-- Increased padding and added margin right -->
                    Register
                </button>
                <a href="/login" class="bg-gray-500 hover:bg-gray-700 text-white font-bold py-4 px-10 rounded focus:outline-none focus:shadow-outline">Log In</a>
            </div>
        </form>
    </div>
</div>