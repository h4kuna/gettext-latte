Překladač pro Latte

Forum: http://forum.nette.org/cs/12021-gettext-na-100-v-sablonach#p86467

1) upravit config podle vzoru config.neon

2) někde v presenteru ve startup spustit $this->context->translator->setLanguage($lang);, aby se pokažde načetli katalogy gettextu