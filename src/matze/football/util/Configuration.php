<?php

declare(strict_types=1);

namespace matze\football\util;

interface Configuration {
    public const MIN_VERTICAL_ENERGY = 0.085;
    public const MIN_HORIZONTAL_ENERGY = 0.115;

    public const DEFAULT_SPEED_LOSS = 0.025;

    public const IN_AIR_SPEED_INCREASE = 0.015;

    public const IMPACT_ENERGY_LOSS = 0.095;
    public const IMPACT_MOTION_LOSS = 0.85;
}