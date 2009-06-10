<?php
try {
    throw new Exception;
}

catch (Exception $e) {
    if (TRUE) {
        print '*';
    }
}
