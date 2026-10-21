<?php

function productImageUrl($image): string
{
    $image = str_replace("\\", "/", trim((string) $image));

    if ($image === "") {
        return "";
    }

    $image = ltrim($image, "/");

    if (strpos($image, "uploads/products/") !== 0) {
        $image = "uploads/products/" . basename($image);
    }

    return BASE_URL . $image;
}
