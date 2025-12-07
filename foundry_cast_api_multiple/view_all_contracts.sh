#!/bin/bash

latest=$(cast block-number)

for ((i=0; i<=$latest; i++)); do
  hex=$(printf "0x%x" $i)
  cast rpc eth_getBlockByNumber $hex true \
    | jq -r '.transactions[] | select(.to == null) | .from + " -> " + .hash'
done

