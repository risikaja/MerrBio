document.addEventListener("DOMContentLoaded", () => {
    let isLoggedIn = false;
    let productsData = []; // Store the products data globally
    
    fetch('./backend/get_session_status.php')
        .then(res => res.json())
        .then(session => {
            isLoggedIn = session.logged_in;
            
            // Set up login/logout button behavior
            const loginBtn = document.getElementById("login-btn");
            const cartBtn = document.getElementById("cart-btn");

            if (session.logged_in) {
                loginBtn.classList.remove("btn-outline-success");
                loginBtn.classList.add("btn-danger");
                loginBtn.textContent = "Log Out";
                cartBtn.classList.remove("d-none");

                loginBtn.addEventListener("click", () => {
                    fetch('./backend/logout.php')
                        .then(() => window.location.href = "index.html");
                });
            } else {
                loginBtn.addEventListener("click", () => {
                    window.location.href = "login.html";
                });
            }
            
            loadProducts(isLoggedIn);
        });
    
    // Set up search and filter event listeners
    const searchInput = document.getElementById("searchInput");
    const categoryFilter = document.getElementById("categoryFilter");
    const sortPriceBtn = document.getElementById("sort-price");
    
    searchInput.addEventListener("input", filterProducts);
    categoryFilter.addEventListener("change", filterProducts);
    
    let priceAscending = true;
    sortPriceBtn.addEventListener("click", () => {
        priceAscending = !priceAscending;
        sortProducts();
        renderProducts(productsData, isLoggedIn);
    });
    
    function filterProducts() {
        const searchTerm = searchInput.value.toLowerCase();
        const selectedCategory = categoryFilter.value;
        
        const filteredProducts = productsData.filter(product => {
            const matchesSearch = product.name.toLowerCase().includes(searchTerm);
            const matchesCategory = selectedCategory === "all" || product.category === selectedCategory;
            
            return matchesSearch && matchesCategory;
        });
        
        renderProducts(filteredProducts, isLoggedIn);
    }
    
    function sortProducts() {
        productsData.sort((a, b) => {
            const priceA = parseFloat(a.price);
            const priceB = parseFloat(b.price);
            
            return priceAscending ? priceA - priceB : priceB - priceA;
        });
    }
    
    function loadProducts(isLoggedIn) {
        fetch('./fetch_products.php')
            .then(res => res.json())
            .then(data => {
                productsData = data; // Store the products data
                renderProducts(productsData, isLoggedIn);
            })
            .catch(err => {
                console.error("Error fetching products:", err);
                document.getElementById('products').innerHTML = "<p>Failed to load products.</p>";
            });
    }
    
    function renderProducts(products, isLoggedIn) {
        const container = document.getElementById('products');
        container.innerHTML = '';
        
        if (products.length === 0) {
            container.innerHTML = "<p>No products match your search criteria.</p>";
            return;
        }
        
        products.forEach(product => {
            const card = document.createElement('div');
            card.className = 'product-card';
            card.dataset.name = product.name;
            card.dataset.category = product.category;
            card.dataset.price = product.price;
            
            card.innerHTML = `
                <div class="card shadow-sm mb-4" style="width: 18rem;">
                    <img src="${product.img_url || './images/default.jpg'}" class="card-img-top" alt="${product.name}">
                    <div class="card-body">
                        <h5 class="card-title">${product.name}</h5>
                        <p class="card-text">${product.text}</p>
                        <p class="card-text fw-bold">${product.price}€ / ${product.unit}</p>
                        <p class="card-text text-muted">${product.category}</p>
                        <button class="btn btn-success add-to-cart-btn" data-id="${product.id}">
                            Add to Cart
                        </button>
                    </div>
                </div>
            `;
            container.appendChild(card);
        });
        
        // Attach event listeners to all buttons
        document.querySelectorAll(".add-to-cart-btn").forEach(button => {
            button.addEventListener("click", (e) => {
                const productId = e.target.dataset.id;
                if (!isLoggedIn) {
                    window.location.href = "login.html";
                } else {
                    addToCart(productId);
                }
            });
        });
    }
});

function addToCart(productId) {
    fetch('./backend/add_to_cart.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ product_id: productId })
    })
    .then(res => res.json())
    .then(data => {
        if (data.success) {
            alert("Product added to cart!");
        } else {
            alert("Failed to add to cart.");
        }
    })
    .catch(err => {
        console.error("Error adding to cart:", err);
    });
}