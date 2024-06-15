#!/bin/bash
apt-get -y install systemd
systemctl list-unit-files --type=service
