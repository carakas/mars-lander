<?php
/**
 * Save the Planet.
 * Use less Fossil Fuel.
 **/

// Write an action using echo(). DON'T FORGET THE TRAILING \n
// To debug: error_log(var_export($var, true)); (equivalent to var_dump)
// 2 integers: rotate power. rotate is the desired rotation angle (should be 0 for level 1), power is the desired thrust power (0 to 4).

// $N: the number of points used to draw the surface of Mars.
fscanf(STDIN, "%d", $N);
for ($i = 0; $i < $N; $i++)
{
    // $landX: X coordinate of a surface point. (0 to 6999)
    // $landY: Y coordinate of a surface point. By linking all the points together in a sequential fashion, you form the surface of Mars.
    fscanf(STDIN, "%d %d", $landX, $landY);
    Terrain::add(new Coordinate($landX, $landY));
}

error_log(Terrain::getTerrain());

$ship = new Ship();
// game loop
while (TRUE)
{
    // $hSpeed: the horizontal speed (in m/s), can be negative.
    // $vSpeed: the vertical speed (in m/s), can be negative.
    // $fuel: the quantity of remaining fuel in liters.
    // $rotate: the rotation angle in degrees (-90 to 90).
    // $power: the thrust power (0 to 4).
    fscanf(STDIN, "%d %d %d %d %d %d %d", $x, $y, $horizontalSpeed, $verticalSpeed, $fuel, $pitch, $thrust);

    $ship->update(new Coordinate($x, $y), $horizontalSpeed, $verticalSpeed, $fuel, $pitch, $thrust);

    echo($ship->__toString());
}

final class Ship
{
    private const MAX_VERTICAL_LANDING_SPEED = 32;
    private const MAX_HORIZONTAL_LANDING_SPEED = 20;
    private const GRAVITY = 3.711;
    private const MIN_THRUST = 0;
    private const MAX_THRUST = 4;

    /** @var Coordinate */
    private $position;

    /** @var int */
    private $verticalSpeed;

    /** @var int */
    private $horizontalSpeed;

    /** @var int */
    private $fuel;

    /** int */
    private $pitch;

    /** int */
    private $thrust;

    public function update(Coordinate $newPosition, $horizontalSpeed, $verticalSpeed, $fuel, $pitch, $thrust): void
    {
        //error_log($newPosition->distanceTo($this->position));
        $this->position = $newPosition;
        //error_log($horizontalSpeed - $this->horizontalSpeed);
        $this->horizontalSpeed = $horizontalSpeed;
        //error_log($verticalSpeed - $this->verticalSpeed);
        $this->verticalSpeed = $verticalSpeed;
        //error_log($fuel - $this->fuel);
        $this->fuel = $fuel;
        //error_log($pitch - $this->pitch);
        $this->pitch = $pitch;
        //error_log($thrust - $this->thrust);
        $this->thrust = $thrust;
    }

    private function getNextPosition(): Coordinate
    {
        return $this->position->subtract(new Coordinate($this->horizontalSpeed, $this->verticalSpeed));
    }

    private function getNextVerticalSpeed(): float
    {
        return $this->verticalSpeed + self::GRAVITY - $this->thrust;
    }

    public function __toString(): string
    {
        $desiredThrust = self::MIN_THRUST;

        $landingSpot = Terrain::currentLandingSpot($this);
        $distanceToLanding = $this->getNextPosition()->distanceTo($landingSpot);
        error_log('Dist:' . $distanceToLanding);
        if ($distanceToLanding > 0) {
            $maxLift = self::MAX_THRUST - self::GRAVITY;
            $rampUp = $this->thrust >= self::MAX_THRUST - 1 ? 0 : array_sum(
                array_map(
                    static function ($thrust): float {
                        return $thrust - self::GRAVITY;
                    },
                    range($this->thrust + 1, self::MAX_THRUST - 1)
                )
            );
            $nextVerticalSpeed = $this->getNextVerticalSpeed();
            $verticalOverspeed = -1 * $nextVerticalSpeed - $rampUp - self::MAX_VERTICAL_LANDING_SPEED;
            $turnsTillOverspeed0 = ceil($verticalOverspeed / $maxLift);
            $turnsTillLandingSpot = ceil($distanceToLanding / ($verticalOverspeed + self::MAX_VERTICAL_LANDING_SPEED));
            error_log('Vertical overspeed:' . $verticalOverspeed);
            error_log('Turns till overspeed 0:' . $turnsTillOverspeed0);
            error_log('Turns till landing spot:' . $turnsTillLandingSpot);

            if ($turnsTillOverspeed0 > $turnsTillLandingSpot) {
                // Initiate suicide burn
                $desiredThrust = 4;
            }
        }


        return '0 ' . $desiredThrust . PHP_EOL;
    }

    public function getPosition(): Coordinate
    {
        return $this->position;
    }
}

final class Terrain
{
    /** @var Coordinate[] */
    private static $coordinates = [];

    /** @var Line */
    private static $landingZone;

    public static function add(Coordinate $coordinate): void
    {
        self::$coordinates[$coordinate->getX()] = $coordinate;
    }

    public static function getLandingZone(): Line
    {
        if (self::$landingZone !== null) {
            return self::$landingZone;
        }
        self::$landingZone = new Line(new Coordinate(0,0), new Coordinate(0,0));
        foreach (self::$coordinates as $coordinate) {
            if ($coordinate->equalY(self::$landingZone->getStart())) {
                self::$landingZone->drawTo($coordinate);
            }

            if (self::$landingZone->isPoint()) {
                self::$landingZone->startAt($coordinate);
            } elseif (!$coordinate->equalY(self::$landingZone->getEnd())) {
                break;
            }
        }

        return self::getLandingZone();
    }

    public static function currentLandingSpot(Ship $ship): Coordinate
    {
        return self::getLandingZone()->getIntersection(
            new Line(
                $ship->getPosition(),
                new Coordinate($ship->getPosition()->getX(), 0)
            )
        );
    }

    public static function getTerrain(): string
    {   
        return 'Terrain: '
            . PHP_EOL
            . implode(PHP_EOL, self::$coordinates)
            . PHP_EOL
            . PHP_EOL
            . self::getLandingZone();
    }
}

final class Line
{
    /** @var Coordinate */
    private $start;

    /** @var Coordinate */
    private $end;

    public function __construct(Coordinate $start, Coordinate $end)
    {
        $this->start = $start;
        $this->end = $end;
    }

    public function getStart(): Coordinate
    {
        return $this->start;
    }

    public function getEnd(): Coordinate
    {
        return $this->end;
    }

    public function startAt(Coordinate $coordinate): void
    {
        $this->start = $coordinate;
        $this->end = $coordinate;
    }

    public function drawTo(Coordinate $end): void
    {
        $this->end = $end;
    }

    public function isPoint(): bool
    {
        return $this->start->equals($this->end);
    }

    public function getIntersection(self $line): Coordinate
    {
        $a1 = $this->getEnd()->getY() - $this->getStart()->getY();
        $b1 = $this->getStart()->getX() - $this->getEnd()->getX();
        $c1 = $a1 * $this->getStart()->getX() + $b1 * $this->getStart()->getY();
 
        $a2 = $line->getEnd()->getY() - $line->getStart()->getY();
        $b2 = $line->getStart()->getX() - $line->getEnd()->getX();
        $c2 = $a2 * $line->getStart()->getX() + $b2 * $line->getStart()->getY();
 
        $delta = $a1 * $b2 - $a2 * $b1;

        return new Coordinate(($b2 * $c1 - $b1 * $c2) / $delta, ($a1 * $c2 - $a2 * $c1) / $delta);
    }

    public function __toString(): string
    {
        return 'Landingzone: '
        . PHP_EOL
        . implode(
            PHP_EOL,
            [
                'start' => $this->start,
                'end' => $this->end,
            ]
        );
    }
}

final class Coordinate
{
    private const STRING_SEPARATOR = ' ';

    /** @var int */
    private $x;

    /** @var int */
    private $y;

    public function __construct(int $x, int $y)
    {
        $this->x = $x;
        $this->y = $y;
    }

    public function getX(): int
    {
        return $this->x;
    }

    public function getY(): int
    {
        return $this->y;
    }

    public function equals(?self $coordinate): bool
    {
        if ($coordinate === null) {
            return false;
        }

        return $this->x === $coordinate->x && $this->y === $coordinate->y;
    }

    public function equalY(?self $coordinate): bool
    {
        if ($coordinate === null) {
            return false;
        }

        return $this->y === $coordinate->y;
    }

    public function add(?self $coordinate): self
    {
        if ($coordinate === null) {
            return $this;
        }

        return new self(
            $this->x + $coordinate->x,
            $this->y + $coordinate->y
        );
    }

    public function subtract(?self $coordinate): self
    {
        if ($coordinate === null) {
            return $this;
        }

        return new self(
            $this->x - $coordinate->x,
            $this->y - $coordinate->y
        );
    }

    public function multiplyByFactor(float $factor): self
    {
        return new self(
            (int) $this->x * $factor,
            (int) $this->y * $factor
        );
    }

    public function distanceTo(?self $coordinate): ?float
    {
        if ($coordinate === null) {
            return null;
        }

        $dX = $this->x - $coordinate->x;
        $dY = $this->y - $coordinate->y;

        return sqrt($dX * $dX + $dY * $dY);
    }

    public static function fromString(string $coordinate): self
    {

        [$x, $y] = explode(self::STRING_SEPARATOR, $coordinate);

        return new self($x, $y);
    }

    public function asString(): string
    {
        return $this->x . self::STRING_SEPARATOR . $this->y;
    }

    public function __toString(): string
    {
        return $this->asString();
    }
}
