# Overview

A local check for pihole instances running on Linux.  There is a special agent in the CheckMK exchange, but it requires more configuration than this one.

# Requirements

- Check-MK host agent
- php 8.x, required and installed by Pi-hole with 5.x Pi-Hole.

# Installation

1. Copy the proper file into the agent local checks directory on the Pi-Hole host
   - pihole5.php for Pi-hole 5.x
   - pihole6.php for Pi-hole 6.x
3. Make sure it is executable (chmod 700).
4. For 6.x, edit the password variable at the beginning of the file.  Comment it out if you run passwordless.
6. Inventory the host in CheckMK.
7. Enjoy query statistics, update status, and enabled/disabled alerts.
