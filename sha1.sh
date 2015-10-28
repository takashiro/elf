echo '<?php exit;?>' > data/sha1.inc.php
git ls-files -s | grep -v ".gitignore" | grep -v "sha1.sh" | cut -c8-47,50- >> data/sha1.inc.php

if [ -d "extension" ]; then
	cd extension
	git ls-files -s | grep -v ".gitignore" | cut -c8-47,50- | sed 's/\t/\textension\//g' >> ../data/sha1.inc.php
	cd ../
fi
