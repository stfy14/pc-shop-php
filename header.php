<?php
require_once 'cart_core.php';

$cart_items = [];
$total_price = 0;
$ids_in_cart = getCartIds();

if (!empty($ids_in_cart)) {
    if (isset($conn)) {
        $ids_safe = array_map('intval', $ids_in_cart);
        $ids_string = implode(',', $ids_safe);
        
        if(!empty($ids_string)) {
            $sql = "SELECT * FROM products WHERE id IN ($ids_string)";
            $stmt_cart = $conn->query($sql);
            while ($row = $stmt_cart->fetch()) {
                $cart_items[] = $row;
                $total_price += $row['price'];
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>PC Shop</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
    
    <style>
        body { background-color: #f4f6f9; font-family: system-ui, -apple-system, sans-serif; }
        
        .card-custom {
            height: 100%; border: none; border-radius: 16px; background: white;
            box-shadow: 0 8px 30px rgba(0, 0, 0, 0.08);
            transition: transform 0.2s, box-shadow 0.2s;
            display: flex; flex-direction: column; 
        }
        .card-custom:hover { transform: translateY(-5px); box-shadow: 0 12px 35px rgba(0, 0, 0, 0.12); }
        .card-img-wrapper { 
            height: 220px; background: #fff; display: flex; align-items: center; justify-content: center; padding: 20px;
            overflow: hidden;
            border-radius: 16px 16px 0 0;
        }
        .card-img-top { max-height: 100%; max-width: 100%; object-fit: contain; }
        .card-body-custom { padding: 20px; flex-grow: 1; display: flex; flex-direction: column; }
        .product-title { font-size: 1.1rem; font-weight: 700; color: #212529; text-decoration: none; margin-bottom: 5px; display: block; }
        .product-title:hover { color: #0d6efd; }
        .product-cat { font-size: 0.85rem; color: #adb5bd; margin-bottom: 15px; font-weight: 500; text-transform: uppercase; letter-spacing: 0.5px; }
        .card-bottom { margin-top: auto; display: flex; justify-content: space-between; align-items: center; }
        .price-tag { font-size: 1.25rem; font-weight: 800; color: #212529; }

        .btn-light-primary {
            color: #0d6efd;
            background-color: rgba(13, 110, 253, 0.1);
            border-color: transparent;
        }
        .btn-light-primary:hover, .btn-light-primary:focus {
            color: #fff;
            background-color: #0d6efd;
            border-color: #0d6efd;
        }
        
        .custom-select-wrapper { position: relative; }
        .custom-select-wrapper select { display: none; }
        .custom-select {
            position: relative;
            cursor: pointer;
            -webkit-user-select: none; user-select: none;
            transition: border-color .15s ease-in-out, box-shadow .15s ease-in-out;
        }
        .custom-select::after {
            content: '';
            position: absolute;
            top: 50%;
            right: 1.25rem;
            transform: translateY(-50%);
            width: 12px;
            height: 12px;
            background-image: url("data:image/svg+xml,%3csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 16 16'%3e%3cpath fill='none' stroke='%23343a40' stroke-linecap='round' stroke-linejoin='round' stroke-width='2.5' d='m2 5 6 6 6-6'/%3e%3c/svg%3e");
            background-repeat: no-repeat;
            background-position: center;
            background-size: contain;
            transition: transform 0.2s ease;
        }
        .custom-select-wrapper.is-open .custom-select {
            border-color: #86b7fe !important;
            box-shadow: 0 0 0 0.25rem rgba(13, 110, 253, .25);
        }
        .custom-select-wrapper.is-open .custom-select::after {
            transform: translateY(-50%) rotate(180deg);
        }
        .custom-select-options {
            position: absolute;
            top: calc(100% + 8px);
            left: 0;
            right: 0;
            background: #fff;
            border-radius: 12px;
            box-shadow: 0 10px 25px rgba(0,0,0,0.1);
            z-index: 10;
            max-height: 265px;
            overflow-y: auto;
            opacity: 0;
            visibility: hidden;
            transform: translateY(10px);
            transition: opacity 0.2s ease, visibility 0s .2s linear, transform 0.2s ease;
            border: 1px solid #dee2e6;
            padding: 5px;
        }
        .custom-select-wrapper.is-open .custom-select-options {
            opacity: 1;
            visibility: visible;
            transform: translateY(0);
            transition: opacity 0.2s ease, visibility 0s 0s linear, transform 0.2s ease;
        }
        .custom-select-option {
            padding: 10px 15px;
            cursor: pointer;
            transition: background-color 0.2s ease, color 0.2s ease;
            border-radius: 8px;
        }
        .custom-select-option:hover { background-color: #f8f9fa; }
        .custom-select-option.is-selected {
            background-color: #0d6efd;
            font-weight: 500;
            color: white;
        }
        .custom-select-options::-webkit-scrollbar { width: 6px; }
        .custom-select-options::-webkit-scrollbar-track { background: transparent; margin-top: 5px; margin-bottom: 5px; }
        .custom-select-options::-webkit-scrollbar-thumb { background-color: #d1d5db; border-radius: 20px; border: 1px solid transparent; background-clip: content-box; }
        .custom-select-options::-webkit-scrollbar-thumb:hover { background-color: #9ca3af; }

        .order-item-link {
            transition: background-color 0.2s ease, box-shadow 0.2s ease;
        }
        .order-item-link:hover {
            background-color: rgba(13, 110, 253, 0.1) !important;
            box-shadow: 0 .125rem .25rem rgba(13, 110, 253, 0.25) !important;
        }

        .chat-container { 
            background: #fff; 
            border-radius: 1rem; 
            overflow: hidden; 
            display: flex; 
            flex-direction: column; 
            height: 100%; 
            border: none; 
            box-shadow: 0 .125rem .25rem rgba(0,0,0,.075); 
        }
        .chat-header { background: #fff; padding: 20px 25px; border-bottom: 1px solid #f5f5f5; font-weight: 700; font-size: 1.1rem; display: flex; align-items: center; }
        .chat-body { flex-grow: 1; padding: 25px; overflow-y: auto; display: flex; flex-direction: column; gap: 15px; background-color: #f8f9fa; }
        .message-bubble { max-width: 80%; padding: 12px 18px; position: relative; font-size: 0.95rem; line-height: 1.5; box-shadow: 0 2px 5px rgba(0,0,0,0.02); word-wrap: break-word; }
        .message-me { align-self: flex-end; background: #3b82f6; color: white; border-radius: 20px 20px 4px 20px; }
        .message-me .msg-time { color: rgba(255,255,255,0.7); font-size: 0.7rem; text-align: right; margin-top: 5px; margin-bottom: -3px; }
        .message-them { align-self: flex-start; background: white; color: #1f2937; border-radius: 20px 20px 20px 4px; border: 1px solid #eaeaea; }
        .message-them .msg-time { color: #9ca3af; font-size: 0.7rem; margin-top: 5px; margin-bottom: -3px; }
        .chat-footer { background: white; padding: 15px 20px; border-top: 1px solid #f0f0f0; }
        .chat-input-group {
            background: #fff;
            border: 2px solid #eef2f6;
            border-radius: 28px;
            display: flex;
            align-items: stretch;
            padding: 4px;
            min-height: 52px;
            box-sizing: border-box;
            transition: border-color 0.2s;
            overflow: hidden;
        }
        .chat-input-group:focus-within {
            border-color: #3b82f6;
            box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1);
        }
        .chat-input-wrapper {
            flex-grow: 1;
            display: grid;
            min-height: 44px;
            transition: grid-template-rows 0.2s ease-out;
            grid-template-rows: 44px;
        }
        .chat-input {
            grid-area: 1 / 1 / 2 / 2;
            width: 100%;
            background: transparent;
            border: none;
            resize: none;
            outline: none;
            padding: 12px 16px;
            font-size: 0.95rem;
            line-height: 20px;
            color: #333;
            box-sizing: border-box;
            margin: 0;
            overflow-y: auto;
        }
        .chat-input::-webkit-scrollbar { width: 6px; }
        .chat-input::-webkit-scrollbar-track { background: transparent; margin-top: 10px; margin-bottom: 10px; }
        .chat-input::-webkit-scrollbar-thumb { background-color: #d1d5db; border-radius: 20px; border: 2px solid transparent; background-clip: content-box; }
        .chat-input::-webkit-scrollbar-thumb:hover { background-color: #9ca3af; }
        .btn-send {
            width: 48px;
            height: auto; 
            flex-shrink: 0;
            border-radius: 24px;
            border: none;
            background: #ffc107;
            color: #000;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            margin-left: 4px;
            transition: background-color 0.2s, border-radius 0.2s ease-out;
        }
        .chat-input-group.is-expanded .btn-send { border-radius: 8px 24px 24px 8px; }
        .btn-send:hover { background: #ffca2c; }
        .btn-send i { font-size: 1.2rem; margin-left: -2px; margin-top: 2px; }
        
        .cart-item { transition: background 0.2s; border-radius: 12px; position: relative; }
        .cart-item:hover { background: #f8f9fa; }
        .cart-item-disabled { opacity: 0.6; background-color: #f8f9fa; }
        .text-strike { text-decoration: line-through; }
    </style>
</head>
<body class="bg-light">

<nav class="navbar navbar-expand-lg navbar-light bg-white shadow-sm mb-4 sticky-top">
  <div class="container">
    <a class="navbar-brand fw-bold" href="index.php">
        <i class="bi bi-pc-display text-primary"></i> PC Shop
    </a>
    
    <button class="navbar-toggler border-0" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
      <span class="navbar-toggler-icon"></span>
    </button>
    
    <div class="collapse navbar-collapse" id="navbarNav">
        
        <form class="d-flex mx-auto my-2 my-lg-0" action="index.php" method="get" style="width: 100%; max-width: 400px;">
            <div class="input-group">
                <input class="form-control border-end-0 rounded-start-pill bg-light border-light" type="search" name="q" placeholder="Поиск товаров..." aria-label="Search" value="<?php echo htmlspecialchars($_GET['q'] ?? ''); ?>">
                <button class="btn btn-light border-start-0 rounded-end-pill border-light" type="submit">
                    <i class="bi bi-search text-muted"></i>
                </button>
            </div>
        </form>

        <div class="navbar-nav ms-auto gap-2 align-items-center">
            <a class="nav-link fw-medium" href="index.php">Каталог</a>
            
            <button class="btn btn-light position-relative rounded-pill px-3 fw-medium" type="button" data-bs-toggle="offcanvas" data-bs-target="#offcanvasCart">
                <i class="bi bi-cart3"></i> Корзина
                <?php if (count($cart_items) > 0): ?>
                    <span class="position-absolute bg-primary rounded-circle d-flex align-items-center justify-content-center border border-2 border-white"
                          style="top: 0; right: 0; transform: translate(10%, -20%); width: 20px; height: 20px; font-size: 0.7rem; color: white !important;">
                        <?php echo count($cart_items); ?>
                    </span>
                <?php endif; ?>
            </button>
            
            <?php if (isset($_SESSION['user_id'])): ?>
                <a class="btn btn-dark rounded-pill px-4" href="profile.php">
                    <i class="bi bi-person-fill"></i> Профиль
                </a>
            <?php else: ?>
                <a class="btn btn-outline-primary rounded-pill px-4" href="login.php">Войти</a>
            <?php endif; ?>
        </div>
    </div>
  </div>
</nav>

<div class="offcanvas offcanvas-end rounded-start-4 border-0 overflow-hidden" tabindex="-1" id="offcanvasCart" style="width: 400px;">
  <div class="offcanvas-header">
    <h5 class="offcanvas-title fw-bold">🛒 Корзина</h5>
    <button type="button" class="btn-close" data-bs-dismiss="offcanvas"></button>
  </div>
  <div class="offcanvas-body d-flex flex-column p-0">
    
    <?php 
        $block_checkout = false;
        
        if (count($cart_items) > 0): 
    ?>
        <div class="flex-grow-1 overflow-auto p-3">
            <?php foreach ($cart_items as $item): 
                $is_ended = ($item['quantity'] <= 0 || $item['is_deleted'] == 1);
                if ($is_ended) $block_checkout = true;
            ?>
                <div class="d-flex align-items-center mb-2 p-2 cart-item <?php echo $is_ended ? 'cart-item-disabled' : ''; ?>">
                    
                    <div class="bg-white rounded border d-flex align-items-center justify-content-center me-3 position-relative" style="width: 60px; height: 60px;">
                        <img src="<?php echo $item['image'] ?: 'https://placehold.co/50'; ?>" style="max-width: 100%; max-height: 100%; mix-blend-mode: multiply;">
                    </div>
                    
                    <div class="flex-grow-1">
                        <div class="fw-bold text-dark lh-sm mb-1 <?php echo $is_ended ? 'text-strike' : ''; ?>">
                            <?php echo htmlspecialchars($item['title']); ?>
                        </div>
                        
                        <?php if($is_ended): ?>
                            <span class="badge bg-secondary rounded-pill py-1 px-2" style="font-size: 0.65rem;">Закончился</span>
                        <?php else: ?>
                            <div class="text-primary fw-bold small"><?php echo number_format($item['price'], 0, '', ' '); ?> ₽</div>
                        <?php endif; ?>
                    </div>

                    <a href="cart_action.php?action=remove&id=<?php echo $item['id']; ?>" class="btn btn-sm btn-light text-danger rounded-circle shadow-sm" style="width: 32px; height: 32px; display: flex; align-items-center; justify-content: center;">
                        <i class="bi bi-trash"></i>
                    </a>
                </div>
            <?php endforeach; ?>
        </div>

        <div class="p-4 bg-light mt-auto border-top">
            <div class="d-flex justify-content-between mb-3">
                <span class="text-muted">Итого:</span>
                <span class="fs-4 fw-bold"><?php echo number_format($total_price, 0, '', ' '); ?> ₽</span>
            </div>
            
            <?php if ($block_checkout): ?>
                <div class="alert alert-warning small py-2 mb-2 text-center">
                    <i class="bi bi-exclamation-circle"></i> Удалите отсутствующие товары
                </div>
                <button class="btn btn-secondary w-100 rounded-pill py-3 fw-bold disabled" disabled>
                    Оформить заказ
                </button>
            <?php else: ?>
                <a href="checkout.php" class="btn btn-primary w-100 rounded-pill py-3 fw-bold shadow-sm">
                    Оформить заказ
                </a>
            <?php endif; ?>
        </div>
    <?php else: ?>
        <div class="d-flex flex-column align-items-center justify-content-center h-100 text-muted">
            <i class="bi bi-cart3 display-1 mb-3 opacity-25"></i>
            <p>Ваша корзина пуста</p>
        </div>
    <?php endif; ?>

  </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

<script>
document.addEventListener('DOMContentLoaded', () => {
    const wrappers = document.querySelectorAll('.custom-select-wrapper');

    function closeAllSelects(exceptThisOne = null) {
        wrappers.forEach(wrapper => {
            if (wrapper !== exceptThisOne) {
                wrapper.classList.remove('is-open');
            }
        });
    }

    wrappers.forEach(wrapper => {
        const trigger = wrapper.querySelector('.custom-select');
        const options = wrapper.querySelectorAll('.custom-select-option');
        const hiddenSelect = wrapper.querySelector('select');
        const selectedDisplay = trigger.querySelector('span');

        trigger.addEventListener('click', (e) => {
            e.stopPropagation();
            closeAllSelects(wrapper);
            wrapper.classList.toggle('is-open');
        });

        options.forEach(option => {
            option.addEventListener('click', () => {
                selectedDisplay.textContent = option.textContent.trim();
                hiddenSelect.value = option.dataset.value;
                hiddenSelect.dispatchEvent(new Event('change')); 
                options.forEach(opt => opt.classList.remove('is-selected'));
                option.classList.add('is-selected');
                wrapper.classList.remove('is-open');
            });
        });
    });

    window.addEventListener('click', () => {
        closeAllSelects();
    });
});
</script>

<script>
document.addEventListener('DOMContentLoaded', () => {
    const imageUploadInput = document.getElementById('imageUpload');
    const imagePreview = document.getElementById('imagePreview');
    const imagePlaceholder = document.getElementById('imagePlaceholder');

    if (imageUploadInput && imagePreview && imagePlaceholder) {
        imageUploadInput.addEventListener('change', function() {
            if (this.files && this.files[0]) {
                const reader = new FileReader();
                
                reader.onload = (e) => {
                    imagePreview.src = e.target.result;
                    imagePreview.style.display = 'block';
                    imagePlaceholder.style.display = 'none';
                };
                
                reader.readAsDataURL(this.files[0]);
            }
        });
    }
});
</script>

<div class="container">