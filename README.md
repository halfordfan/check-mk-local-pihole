# Overview

A local check for pihole instances running on Linux.  There is a special agent in the CheckMK exchange, but it will require more configuration.

# Requirements

php 8.x, already required by Pi-Hole.

# Installation

1. Copy the file into the agent local checks directory on the Pi-Hole host.
2. Make sure it is executable (chmod 700)
3. Inventory the host in CheckMK.
4. Enjoy query statistics, update status, and enabled/disabled alerts.
