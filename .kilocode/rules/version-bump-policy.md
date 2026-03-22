---
trigger: always_on
---

* Whenever code changes are made (bug fixes, new features, improvements), automatically bump the version to the next release
* Follow semantic versioning (MAJOR.MINOR.PATCH)
* Update version in: vaptsecure.php (main plugin file)
* Increment PATCH for bug fixes and improvements, MINOR for new features, MAJOR for breaking changes
* After bumping version, also update the VERSION_HISTORY.md file with the changes made
* Ensure version number is consistent across all files