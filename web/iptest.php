<?php

print "<p>REMOTE_ADDR: " . htmlspecialchars($_SERVER['REMOTE_ADDR']) . "</p>";
print "<p>HTTP_X_FORWARDED_FOR: " . htmlspecialchars($_SERVER['HTTP_X_FORWARDED_FOR']) . "</p>";

