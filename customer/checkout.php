<?php
session_start();
include "../includes/db.php";

if (!isset($_SESSION['user_id']) || $_SESSION['role'] != "customer") {
    header("Location: ../login.php");
    exit;
}

if (empty($_SESSION['cart'])) {
    echo "Cart is empty!";
    exit;
}

$user_id = $_SESSION['user_id'];
$total = 0;

/* Calculate Total */
foreach ($_SESSION['cart'] as $item) {
    $total += $item['price'] * $item['quantity'];
}

/* Insert into orders table */
$order_sql = "INSERT INTO orders (customer_id, total_amount)
              VALUES ('$user_id', '$total')";
mysqli_query($conn, $order_sql);

$order_id = mysqli_insert_id($conn);

/* Insert into order_items */
foreach ($_SESSION['cart'] as $id => $item) {

    $name = $item['name'];
    $price = $item['price'];
    $quantity = $item['quantity'];

    $item_sql = "INSERT INTO order_items (order_id, menu_item_id, quantity, price)
                 VALUES ('$order_id', '$id', '$quantity', '$price')";
    mysqli_query($conn, $item_sql);
}

/* Clear Cart */
unset($_SESSION['cart']);

header("Location: payment.php?order_id=".$order_id);
exit;
?>