# Overview

A local check for pihole instances running on Linux.  There is a special agent in the CheckMK exchange, but it requires more configuration than this one.

# Requirements

- Check-MK host agent
- php 8.x, already required and installed by Pi-Hole.

# Installation

1. Copy the file into the agent local checks directory on the Pi-Hole host.
2. Make sure it is executable (chmod 700).
3. Inventory the host in CheckMK.
4. Enjoy query statistics, update status, and enabled/disabled alerts.
