document.addEventListener('DOMContentLoaded', function () {
    const list = document.querySelector('[data-collection-list]');
    if (!list) return;

    const cards = Array.from(list.querySelectorAll('[data-collection-card]'));
    const currencySelect = document.getElementById('collection-filter-currency');
    const noResults = document.getElementById('collection-filter-no-results');

    if (currencySelect) {
        const currencies = [...new Set(cards.map(card => card.dataset.currency).filter(Boolean))];
        currencies.forEach(currency => {
            const option = document.createElement('option');
            option.value = currency;
            option.textContent = currency;
            currencySelect.appendChild(option);
        });

        const initialCurrency = currencySelect.dataset.initialValue || '';
        if (initialCurrency !== '') {
            currencySelect.value = initialCurrency;
        }
    }

    const applyFilters = function () {
        const name = document.getElementById('collection-filter-name').value.trim().toLowerCase();
        const sort = document.getElementById('collection-filter-sort').value;
        const currency = document.getElementById('collection-filter-currency').value;
        const minPrice = parseFloat(document.getElementById('collection-filter-min-price').value);
        const maxPrice = parseFloat(document.getElementById('collection-filter-max-price').value);
        const inStock = document.getElementById('collection-filter-in-stock').checked;
        const oneOfOne = document.getElementById('collection-filter-one-of-one').checked;
        const hasMin = !isNaN(minPrice);
        const hasMax = !isNaN(maxPrice);

        let visible = cards.filter(function (card) {
            const price = card.dataset.price !== '' ? parseFloat(card.dataset.price) : null;
            const cardCurrency = card.dataset.currency;
            const cardName = (card.dataset.name || '').toLowerCase();

            if (name && !cardName.includes(name)) return false;
            if (inStock && card.dataset.inStock !== '1') return false;
            if (oneOfOne && card.dataset.oneOfOne !== '1') return false;
            if (currency && cardCurrency !== currency) return false;

            if (hasMin || hasMax) {
                if (price === null) return false;
                if (hasMin && price < minPrice) return false;
                if (hasMax && price > maxPrice) return false;
            }

            return true;
        });

        if (sort === 'newest') {
            visible.sort((a, b) => Number(b.dataset.created) - Number(a.dataset.created));
        } else if (sort === 'price-asc') {
            visible.sort((a, b) => {
                const aPrice = a.dataset.price !== '' ? parseFloat(a.dataset.price) : Infinity;
                const bPrice = b.dataset.price !== '' ? parseFloat(b.dataset.price) : Infinity;
                return aPrice - bPrice;
            });
        } else if (sort === 'price-desc') {
            visible.sort((a, b) => {
                const aPrice = a.dataset.price !== '' ? parseFloat(a.dataset.price) : -Infinity;
                const bPrice = b.dataset.price !== '' ? parseFloat(b.dataset.price) : -Infinity;
                return bPrice - aPrice;
            });
        } else if (sort === 'rating-desc') {
            visible.sort((a, b) => parseFloat(b.dataset.rating) - parseFloat(a.dataset.rating));
        }

        cards.forEach(card => {
            card.style.display = 'none';
        });

        visible.forEach(card => {
            card.style.display = '';
            list.appendChild(card);
        });

        if (noResults) {
            noResults.style.display = visible.length === 0 ? 'block' : 'none';
        }
    };

    const resetFilters = function () {
        document.getElementById('collection-filter-name').value = '';
        document.getElementById('collection-filter-sort').value = 'default';
        document.getElementById('collection-filter-currency').value = '';
        document.getElementById('collection-filter-min-price').value = '';
        document.getElementById('collection-filter-max-price').value = '';
        document.getElementById('collection-filter-in-stock').checked = false;
        document.getElementById('collection-filter-one-of-one').checked = false;
        applyFilters();
    };

    window.applyCollectionFilters = applyFilters;
    window.resetCollectionFilters = resetFilters;

    const hasInitialFilters =
        document.getElementById('collection-filter-name').value.trim() !== '' ||
        document.getElementById('collection-filter-sort').value !== 'default' ||
        document.getElementById('collection-filter-currency').value !== '' ||
        document.getElementById('collection-filter-min-price').value !== '' ||
        document.getElementById('collection-filter-max-price').value !== '' ||
        document.getElementById('collection-filter-in-stock').checked ||
        document.getElementById('collection-filter-one-of-one').checked;

    if (hasInitialFilters) {
        applyFilters();
    }
});
