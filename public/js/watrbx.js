document.cookie = `timezone=${Intl.DateTimeFormat().resolvedOptions().timeZone}; path=/`; // tuiezoesfposfpgjafkf'

function addCountdownTimer(element, unixTimestamp) {
  const rtf = new Intl.RelativeTimeFormat("en", {
    numeric: "auto",
  });

  function format() {
    const now = Date.now();
    const target = unixTimestamp * 1000;
    const diff = target - now;

    const seconds = Math.round(diff / 1000);
    const minutes = Math.round(seconds / 60);
    const hours = Math.round(minutes / 60);
    const days = Math.round(hours / 24);

    if (Math.abs(seconds) < 60) {
      return rtf.format(seconds, "second");
    } else if (Math.abs(minutes) < 60) {
      return rtf.format(minutes, "minute");
    } else if (Math.abs(hours) < 24) {
      return rtf.format(hours, "hour");
    } else {
      return rtf.format(days, "day");
    }
  }

  function update() {
    element.textContent = format();
  }

  update();

  const interval = setInterval(update, 1000);

  return () => clearInterval(interval);
}