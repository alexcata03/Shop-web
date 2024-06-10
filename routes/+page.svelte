<script lang="ts">
    import {onMount} from 'svelte'
    import {getAllProducts} from "../api/products"
    import ProductCard from "../components/ProductCard.svelte"
    import type Product from '../interfaces/products';

    let products: Product[] = [];

    onMount(async () => {
        try {
            const response = await getAllProducts();
            
            if (response === undefined) {
                throw new Error("Error at fetching all products");
            }

            products = response.products;

            products = products.filter((product) => {
                return product.category === "keyboard"
            });

            console.log(products)
        } catch (error) {
            console.error("Error at fetching products: " + error);
        }
    })

</script>

<div class="container mx-4 p-4">
    <h1 class="text-2xl font-bold mb-4">Products: </h1>
    <div class="grid gap-4 sm:grid-cols-1 md:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4">
        {#each products as product}
            <ProductCard {product} />
        {/each}
    </div>
</div>




