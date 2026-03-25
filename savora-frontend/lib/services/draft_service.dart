import 'dart:convert';
import 'package:flutter/foundation.dart';
import 'package:shared_preferences/shared_preferences.dart';

/// DraftService — menyimpan draft form ke SharedPreferences
/// Dipanggil dari CreateRecipeScreen & EditRecipeScreen
class DraftService {
  static const String _createDraftKey = 'draft_create_recipe';
  static const String _editDraftPrefix = 'draft_edit_recipe_';

  // ─────────────────────────────────────────────
  // CREATE DRAFT
  // ─────────────────────────────────────────────

  /// Simpan draft untuk form Create Recipe
  static Future<void> saveCreateDraft(Map<String, dynamic> data) async {
    try {
      final prefs = await SharedPreferences.getInstance();
      data['saved_at'] = DateTime.now().toIso8601String();
      await prefs.setString(_createDraftKey, json.encode(data));
      debugPrint('[DraftService] Create draft saved');
    } catch (e) {
      debugPrint('[DraftService] saveCreateDraft error: $e');
    }
  }

  /// Load draft untuk form Create Recipe
  static Future<Map<String, dynamic>?> loadCreateDraft() async {
    try {
      final prefs = await SharedPreferences.getInstance();
      final raw = prefs.getString(_createDraftKey);
      if (raw == null || raw.isEmpty) return null;
      return Map<String, dynamic>.from(json.decode(raw));
    } catch (e) {
      debugPrint('[DraftService] loadCreateDraft error: $e');
      return null;
    }
  }

  /// Hapus draft Create Recipe (setelah submit berhasil)
  static Future<void> clearCreateDraft() async {
    try {
      final prefs = await SharedPreferences.getInstance();
      await prefs.remove(_createDraftKey);
      debugPrint('[DraftService] Create draft cleared');
    } catch (e) {
      debugPrint('[DraftService] clearCreateDraft error: $e');
    }
  }

  /// Cek apakah ada draft Create Recipe
  static Future<bool> hasCreateDraft() async {
    try {
      final prefs = await SharedPreferences.getInstance();
      return prefs.containsKey(_createDraftKey);
    } catch (e) {
      return false;
    }
  }

  // ─────────────────────────────────────────────
  // EDIT DRAFT
  // ─────────────────────────────────────────────

  /// Simpan draft untuk form Edit Recipe (per recipe ID)
  static Future<void> saveEditDraft(String recipeId, Map<String, dynamic> data) async {
    try {
      final prefs = await SharedPreferences.getInstance();
      data['saved_at'] = DateTime.now().toIso8601String();
      await prefs.setString('$_editDraftPrefix$recipeId', json.encode(data));
      debugPrint('[DraftService] Edit draft saved for $recipeId');
    } catch (e) {
      debugPrint('[DraftService] saveEditDraft error: $e');
    }
  }

  /// Load draft untuk Edit Recipe
  static Future<Map<String, dynamic>?> loadEditDraft(String recipeId) async {
    try {
      final prefs = await SharedPreferences.getInstance();
      final raw = prefs.getString('$_editDraftPrefix$recipeId');
      if (raw == null || raw.isEmpty) return null;
      return Map<String, dynamic>.from(json.decode(raw));
    } catch (e) {
      debugPrint('[DraftService] loadEditDraft error: $e');
      return null;
    }
  }

  /// Hapus draft Edit Recipe
  static Future<void> clearEditDraft(String recipeId) async {
    try {
      final prefs = await SharedPreferences.getInstance();
      await prefs.remove('$_editDraftPrefix$recipeId');
      debugPrint('[DraftService] Edit draft cleared for $recipeId');
    } catch (e) {
      debugPrint('[DraftService] clearEditDraft error: $e');
    }
  }

  /// Cek apakah ada draft Edit Recipe
  static Future<bool> hasEditDraft(String recipeId) async {
    try {
      final prefs = await SharedPreferences.getInstance();
      return prefs.containsKey('$_editDraftPrefix$recipeId');
    } catch (e) {
      return false;
    }
  }
}