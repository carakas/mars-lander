<?php
/**
 * Save the Planet.
 * Use less Fossil Fuel.
 **/

// $N: the number of points used to draw the surface of Mars.
fscanf(STDIN, "%d", $N);
for ($i = 0; $i < $N; $i++)
{
    // $landX: X coordinate of a surface point. (0 to 6999)
    // $landY: Y coordinate of a surface point. By linking all the points together in a sequential fashion, you form the surface of Mars.
    fscanf(STDIN, "%d %d", $landX, $landY);
}

// game loop
while (TRUE)
{
    // $HS: the horizontal speed (in m/s), can be negative.
    // $VS: the vertical speed (in m/s), can be negative.
    // $F: the quantity of remaining fuel in liters.
    // $R: the rotation angle in degrees (-90 to 90).
    // $P: the thrust power (0 to 4).
    fscanf(STDIN, "%d %d %d %d %d %d %d", $X, $Y, $HS, $VS, $F, $R, $P);

    // Write an action using echo(). DON'T FORGET THE TRAILING \n
    // To debug: error_log(var_export($var, true)); (equivalent to var_dump)


    // R P. R is the desired rotation angle. P is the desired thrust power.
    echo("-20 3\n");
}
?>
