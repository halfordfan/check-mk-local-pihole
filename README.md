# Overview

A local check for pihole instances running on Linux.  There is a special agent in the CheckMK exchange, but it requires more configuration than this one.

# Requirements

- Check-MK host agent
- The python version requires python 3.x (duh) with requests and time modules.
- The legacy PHP version needs php 8.x, required and installed with 5.x Pi-Hole.

# Installation

Current Python 3.x version for Pi-hole 6.x
   1. Copy the pihole6.py into the agent local checks directory on the Pi-Hole host
   2. Make sure it is executable (chmod 700).
   3. Edit the password variable at the beginning of the file.  Comment it out if you run passwordless.
   4. Inventory the host in CheckMK.
   5. Enjoy query statistics, update status, and enabled/disabled alerts.

Legacy PHP version
   1. Copy the proper file into the agent local checks directory on the Pi-Hole host
      - pihole5.php for Pi-hole 5.x
      - pihole6.php for Pi-hole 6.x
   2. Make sure it is executable (chmod 700).
   3. For 6.x, edit the password variable at the beginning of the file.  Comment it out if you run passwordless.
   4. Inventory the host in CheckMK.
   5. Enjoy query statistics, update status, and enabled/disabled alerts.
