#!/usr/bin/env python3

# A straight-up ChatGPT conversion of the pihole6.php
# CheckMK local check by halfordfan et al.

import requests
import time

# Uncomment and set this if you have a password for the Pi-hole UI.
MP = '0Icu812!'

base_url = "http://localhost/api"
sid = ""

if 'MP' in globals():
    auth_url = f"{base_url}/auth"
    payload = {"password": MP}
    headers = {'Content-Type': 'application/json'}

    response = requests.post(auth_url, json=payload, headers=headers)
    result = response.json()

    if not result.get('session', {}).get('valid', False):
        raise SystemExit("Failed to contact Pi-Hole API endpoint. Aborting!")

    sid = f"?sid={result['session']['sid']}"

# Get Pi-hole blocking status
status_url = f"{base_url}/dns/blocking{sid}"
response = requests.get(status_url)
if not response.ok:
    raise SystemExit("API returned no data! Check password and access lists!")

status_data = response.json()
if status_data.get("blocking") == "enabled":
    print('0 "Pi-Hole Status" enabled=1 Pi-Hole ad blocking is enabled')
else:
    print('1 "Pi-Hole Status" enabled=0 Pi-Hole ad blocking is disabled')

# Get Pi-hole summary stats
summary_url = f"{base_url}/stats/summary{sid}"
response = requests.get(summary_url)
summary_data = response.json()

if "queries" not in summary_data:
    raise SystemExit("Failed to contact Pi-Hole API endpoint. Aborting!")

metrics = {
    'total_queries': f'total_queries={summary_data["queries"]["total"]}',
    'blocked_queries': f'blocked_queries={summary_data["queries"]["blocked"]}',
    'percent_blocked': f'percent_blocked={round(summary_data["queries"]["percent_blocked"], 1)}',
    'domains_being_blocked': f'domains_being_blocked={summary_data["gravity"]["domains_being_blocked"]}',
    'cached_queries': f'cached_queries={summary_data["queries"]["cached"]}',
    'forwarded_queries': f'forwarded_queries={summary_data["queries"]["forwarded"]}',
    'frequency': f'frequency={round(summary_data["queries"]["frequency"], 1)}',
    'clients': f'clients={summary_data["clients"]["total"]}'
}
print(f'0 "Pi-Hole Summary" {"|".join(metrics.values())} Pi-Hole Statistics Summary {", ".join(metrics.values()).replace("=",": ")}')

# Check last gravity update
last_gravity = int(time.time()) - summary_data["gravity"]["last_update"]
days_old = int(round(last_gravity / 86400, 0))
print(f'P "Pi-Hole Gravity" last_update={days_old};8;15 Pi-Hole Gravity lists were updated ', end='')

if last_gravity < 60:
    time_value, unit = last_gravity, "second"
elif 60 <= last_gravity < 3600:
    time_value, unit = int(round(last_gravity / 60, 0)), "minute"
elif 3600 <= last_gravity < 86400:
    time_value, unit = int(round(last_gravity / 3600, 0)), "hour"
else:
    time_value, unit = days_old, "day"

print(f"{time_value} {unit}{'s' if time_value != 1 else ''} ago")

# Check for Pi-hole updates
update = 0
message = "Pi-Hole is up to date "
updates_url = f"{base_url}/info/version{sid}"
response = requests.get(updates_url)
updates_data = response.json()
metrics = []

for key in ["core", "web", "ftl"]:
    if updates_data["version"][key]["local"]["version"] != updates_data["version"][key]["remote"]["version"]:
        metrics.append(f"{key}=1")
        update = 1
        message = "Pi-Hole update available (run 'pihole -up'), "
    else:
        metrics.append(f"{key}=0")

print(f'{update} "Pi-Hole Update" {"|".join(metrics)} {message}({", ".join(metrics).replace("=", ": ").replace("0", "current").replace("1", "update needed")})')

# Logout if authenticated
if 'MP' in globals():
    logout_url = f"{auth_url}{sid}"
    requests.delete(logout_url, headers={'accept': 'application/json'})
