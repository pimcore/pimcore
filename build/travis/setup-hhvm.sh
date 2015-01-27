#!/bin/bash

hhvm -m daemon -vServer.Type=fastcgi -vServer.Port=9000 -vServer.FixPathInfo=true
