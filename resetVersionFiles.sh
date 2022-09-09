#!/bin/bash

inputFilelist="composer.json composer.lock version.txt src/entrypoint.php src/style.css"

for f in ${inputFilelist}; do \
  if [[ -f "${f}" ]]; then \
     finalFilelist="$finalFilelist ${f}"; \
  fi; \
done

echo Reverting ${finalFilelist}

git checkout -- ${finalFilelist}
