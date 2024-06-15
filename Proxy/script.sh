#!/bin/bash
apt-get install systemd
systemctl list-unit-files --type=service
