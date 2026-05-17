<?php
print_r(Schema::getColumnListing('products'));
print_r(DB::select('DESCRIBE holidays'));
print_r(DB::select('DESCRIBE purposes'));
