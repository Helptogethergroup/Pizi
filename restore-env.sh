#!/bin/bash
ENV_STORE="$HOME/env-store"
case "$PWD" in
  */public_html/test)
    cp "$ENV_STORE/test.env" .env
    echo "test.env restored"
    ;;
  */public_html)
    cp "$ENV_STORE/live.env" .env
    echo "live.env restored"
    ;;
  *)
    echo "Run this from inside public_html or public_html/test"
    ;;
esac
