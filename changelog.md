# gooseRSS

Versioning is loose and lax, in fact there are no actual versions.  
But here is the list of changes made over time, sorted by 'release' date.

# March 15, 2026
- Rollback: Reformatted the watch page url to use separate arguments again
- Fix: Feed formatting to use CDATA in item links so arguments don't get messed up
- Fix: Better formatting for errors that go into the log
- Change: Now uses cURL for all outgoing requests to better mimic a browser request
- Change: Watch page now uses youtube.com instead of youtube-nocookie.com
- Change: Watch page thumbnail optional and now comes from cache
- Change: YouTube Channel ID stored in main cache file
- New: Expired cache files deleted at the end of each run through cache_delete()

# March 12, 2026
- Change: Improved handling for IMDb ID for eztv
- Change: Less complex formatting for IMDb ID
- Added: Links section for TV Show items
- Added: TV Show thumbnail clickable to start download

# March 11, 2026
- Change: Removed @ from handle and only add it when finding the Channel ID
- Change: Reformatted the watch page url to work with NetNewsWire
- Fixed: Unique item filter when importing data
- Fixed: Watch page linking up with the wrong cached title
- Fixed missing &id from watch page urls

# March 9, 2026
- First release.