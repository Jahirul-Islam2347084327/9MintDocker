## 9Mint — Web Flows (Blade UI & React Components)

This doc describes the **Blade-based web experience** wired up in `routes/web.php` and how it connects to the database, API layer, and embedded React components.

---

## Public pages

- **Homepage**  
  - Route: `GET /` and `GET /homepage` → `HomeController@index`  
  - Shows featured content and entry points into collections/products.

- **Static information pages**  
  - Cart & checkout shells are Blade views:  
    - `GET /cart` → `resources/views/cart.blade.php`  
    - `GET /checkout` → `resources/views/checkout.blade.php`  
  - Marketing / info pages:  
    - `GET /pricing` → `pricing.blade.php`  
    - `GET /contactUs` → `contact-us.blade.php` (Livewire)
    - `GET /contactUs/terms` → `terms-and-conditions.blade.php`  
    - `GET /contactUs/faqs` → `faqs.blade.php`

- **Products & collections**  
  - All products page: `GET /products` → `ProductsController@index`.  
  - Dynamic collections:  
    - Old URLs still work and redirect into the new dynamic system:  
      - `/products/Glossy-collection` → redirects to slug `glossy-collection`  
      - `/products/SuperheroCollection` → redirects to slug `superhero-collection`  
    - Canonical route:  
      - `GET /products/{slug}` → `CollectionPageController@show` (named `collections.show`)  
      - `{slug}` is a collection slug backed by the `collections` and `nfts` tables.

- **User Profiles & Inventory**
  - Public profile: `GET /profile/{username}` → Shows user details, owned NFTs, and seller feedback.
  - Public inventory: `GET /inventory/{username}` → Shows a user's public NFT inventory.

---

## Auth & profile

- **Guest-only routes**  
  - `GET /login` → login/register Blade (`AuthController@showLogin`)  
  - `GET /register` → same view (`AuthController@showRegister`)  
  - `POST /login` → `AuthController@loginWeb` (uses **username + password**)  
  - `POST /register` → `AuthController@registerWeb` (collects **username, email, password**)
  - Password Reset flows (`/forgot-password`, `/reset-password`).

- **Authenticated routes** (wrapped in `Route::middleware('auth')`)  
  - Logout: `POST /logout` → `AuthController@logout`  
  - Profile settings: `GET /profile/settings` → `AuthController@profile`  
  - Profile update: `PATCH /profile` → `AuthController@updateProfile`  
  - Password update: `PATCH /profile/password` → `AuthController@updatePassword`
  - My Inventory: `GET /inventory` → `InventoryController@index`
  - My Listings: `GET /listings` → `InventoryController@listings`

- **Social & Community Features**
  - Friendships: Send, accept, decline, or unfriend users (`/friends/{user}/*`).
  - Chat & Tickets: Real-time messaging and support tickets via Livewire (`/chat/*`).
  - Reviews: Submit or delete NFT reviews (`/nfts/{nft}/review`).
  - Seller Feedback: Rate and leave feedback for other sellers (`/profile/{username}/feedback`).

- **User model fields**  
  - `name` (used as **username**), `email`, `password`, `role`, and **`wallet_address`** (nullable) are mass assignable.  
  - `wallet_address` is added by a later migration and surfaced on the profile form as the NFT wallet address.

---

## Cart, Orders & Checkout

All of the following routes live inside the `auth` middleware group so only logged-in users can place orders.

- **Add to cart**  
  - `POST /cart`  
  - Reads `listing_id` from the form.  
  - Stores items in the **DB-backed cart** (`cart_items`) keyed by user + listing.  
  - Returns back with flash status messages for success / validation errors.

- **Remove from cart**  
  - `DELETE /cart/{id}`  
  - Removes the cart row and flashes a status message.

- **View orders & sales**  
  - `GET /orders` → Blade view `orders.index`.  
  - Loads current user's `orders` (purchases) and `sales` (items sold to others).
  - Refund requests: `GET /orders/{order}/refund-form` and `POST /orders/{order}/refund-request`.

- **Checkout**  
  - `GET /checkout` creates a pending order with a **locked quote** and expiry timestamp.  
  - The order stores pay/ref totals and FX metadata.  
  - If the checkout expires, the user must return to the cart.
  - Supports advanced simulated payment rails (Bank, Crypto, Platform Wallet).

- **Place order**  
  - `POST /orders`  
  - Uses the existing pending order and completes payment via the simulated payment orchestrator.  
  - Listing is marked sold and token ownership is transferred to the buyer on the simulated blockchain ledger (`ChainTransaction`, `ChainCurrentOwnership`).  
  - Clears the checkout session key and redirects back to `/orders` with a success message.

---

## Admin Panel

Protected by `auth` and `admin` middleware:
- **Dashboard:** `GET /admin/dashboard`
- **Approvals:** Review and approve/reject newly created collections and NFTs.
- **Inventory & Orders:** View global inventory, orders, and manage refund requests (`/admin/refunds`).
- **User Management:** Ban/unban users, edit user details, and delete accounts.
- **Tickets:** Livewire-based support ticket management (`/admin/tickets`).

---

## Relationship to the API & React

- The **API** under `/api/v1/**` exposes collections, NFTs, market history, a **DB-backed cart**, favourites, checkout/order endpoints, and search suggestions for SPA or external clients.
- The **Blade web flows** described here use the same underlying models (`Collection`, `Nft`, `Order`, `OrderItem`, `User`) but operate via standard web routes.
- **React Integration:** The frontend uses React 19 (via Vite) for highly interactive components like the **NFT Discovery Board** and **Marketplace Widgets**. These components are embedded directly into Blade views and communicate with the `/api/v1` endpoints.


